<?php
/**
 * Расширение класса BrowserProcess 
 * 
 * @author Alex Ryassky 
 * @copyright (c) 2020, M1 <m1-shop.ru> 
 */
namespace Components\GHC;

use HeadlessChromium\Browser\BrowserProcess;
use Symfony\Component\Process\Process;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Utils;

class BrowserProcessExt extends BrowserProcess
{

    /**
     * Wait for chrome to startup (given a process) and return the ws uri to connect to
     * @param Process $process
     * @param int $timeout
     * @return mixed
     */
    private function waitForStartup(Process $process, int $timeout)
    {
        // log
        $this->logger->debug('process: waiting for ' . $timeout / 1000000 . ' seconds for startup');

        try {
            $generator = function (Process $process) {
                while (true) {
                    if (!$process->isRunning()) {
                        // log
                        $this->logger->debug('process: ✗ chrome process stopped');

                        // exception
                        $message = 'Chrome process stopped before startup completed.';
                        $error   = trim($process->getErrorOutput());
                        if (!empty($error)) {
                            $message .= ' Additional info: ' . $error;
                        }
                        throw new \RuntimeException($message);
                    }

                    $output = trim($process->getIncrementalErrorOutput());

                    if ($output) {
                        // log
                        $this->logger->debug('process: chrome output:' . $output);

                        $outputs = explode(PHP_EOL, $output);

                        foreach ($outputs as $output) {
                            $output = trim($output);

                            // ignore empty line
                            if (empty($output)) {
                                continue;
                            }

                            // find socket uri
                            if (preg_match('/DevTools listening on (ws:\/\/.*)/', $output, $matches)) {
                                // log
                                $this->logger->debug('process: ✓ accepted output');
                                return $matches[1];
                            } else {
                                // log
                                $this->logger->debug('process: ignoring output:' . trim($output));
                            }
                        }
                    }

                    // wait for 10ms
                    yield 10 * 1000;
                }
            };
            return Utils::tryWithTimeout($timeout, $generator($process));
        } catch (OperationTimedOut $e) {
            throw new \RuntimeException('Cannot start browser', 0, $e);
        }
    }

    /**
     * Get args for creating chrome's startup command
     * @param array $options
     * @return array
     */
    private function getArgsFromOptions($binary, array $options)
    {
        // command line args to add to start chrome (inspired by puppeteer configs)
        // see https://peter.sh/experiments/chromium-command-line-switches/
        $args = [
            $binary,
            // auto debug port
            '--remote-debugging-port=0',
            // disable undesired features
            '--disable-background-networking',
            '--disable-background-timer-throttling',
            '--disable-client-side-phishing-detection',
            '--disable-default-apps',
            '--disable-extensions',
            '--disable-hang-monitor',
            '--disable-popup-blocking',
            '--disable-prompt-on-repost',
            '--disable-sync',
            '--disable-translate',
            '--metrics-recording-only',
            '--no-first-run',
            '--safebrowsing-disable-auto-update',
            // automation mode
            '--enable-automation',
            // password settings
            '--password-store=basic',
            '--use-mock-keychain', // osX only
        ];

        // enable headless mode
        if (!array_key_exists('headless', $options) || $options['headless']) {
            $args[] = '--headless';
            $args[] = '--disable-gpu';
            $args[] = '--hide-scrollbars';
            $args[] = '--mute-audio';
        }

        // disable loading of images (currently can't be done via devtools, only CLI)
        if (array_key_exists('enableImages', $options) && ($options['enableImages']
            === false)) {
            $args[] = '--blink-settings=imagesEnabled=false';
        }

        // window's size
        if (array_key_exists('windowSize', $options) && $options['windowSize']) {
            if (!is_array($options['windowSize']) ||
                count($options['windowSize']) !== 2 ||
                !is_numeric($options['windowSize'][0]) ||
                !is_numeric($options['windowSize'][1])
            ) {
                throw new \InvalidArgumentException(
                'Option "windowSize" must be an array of dimensions (eg: [1000, 1200])'
                );
            }

            $args[] = '--window-size=' . implode(',', $options['windowSize']);
        }

        // sandbox mode - useful if you want to use chrome headless inside docker
        if (array_key_exists('noSandbox', $options) && $options['noSandbox']) {
            $args[] = '--no-sandbox';
        }

        // user agent
        if (array_key_exists('userAgent', $options)) {
            $args[] = '--user-agent=' . $options['userAgent'];
        }

        // ignore certificate errors
        if (array_key_exists('ignoreCertificateErrors', $options) && $options['ignoreCertificateErrors']) {
            $args[] = '--ignore-certificate-errors';
        }

        // add custom flags
        if (array_key_exists('customFlags', $options) && is_array($options['customFlags'])) {
            $args = array_merge($args, $options['customFlags']);
        }

        // add user data dir to args
        $args[] = '--user-data-dir=' . realpath($options['userDataDir']);

        return $args;
    }

    /**
     *  Получить командную строку запуска
     * 
     * @return string
     */
    public function getCommandLine()
    {
        return ($this->process) ?
            $this->process->getCommandLine() : false;
    }

    /**
     * Запуск процесса 
     * 
     * @param string $binary путь к экзешнику
     * @param array $options
     * 
     * @return \HeadlessChromium\Browser\Process
     * @throws \RuntimeException
     */
    public function start($binary, $options)
    {
        if ($this->wasStarted) {
            // cannot start twice because once started this class contains the necessary data to cleanup the browser.
            // starting in again would result in replacing those data.
            throw new \RuntimeException('This process was already started');
        }

        $this->wasStarted = true;

        // log
        $this->logger->debug('process: initializing');

        // user data dir
        if (!array_key_exists('userDataDir', $options) || !$options['userDataDir']) {
            // if no data dir specified create it
            $options['userDataDir'] = $this->createTempDir();

            // set user data dir to get removed on close
            $this->userDataDirIsTemp = true;
        }
        $this->userDataDir = $options['userDataDir'];

        // log
        $this->logger->debug('process: using directory: ' . $options['userDataDir']);

        // get args for command line
        $args = $this->getArgsFromOptions($binary, $options);

        // setup chrome process
        $process       = new Process($args);
        $this->process = $process;

        // log
        $this->logger->debug('process: starting process: ' . $process->getCommandLine());

        // and start
        $process->start();

        // wait for start and retrieve ws uri
        $startupTimeout = $options['startupTimeout'] ?? 60;
        $this->wsUri    = $this->waitForStartup($process, $startupTimeout * 1000 * 1000);

        // log
        $this->logger->debug('process: connecting using ' . $this->wsUri);

        // connect to browser
        $connection = new Connection($this->wsUri, $this->logger, $options['sendSyncDefaultTimeout'] ?? 3000);
        $connection->connect();

        // connection delay
        if (array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        // set connection to allow killing chrome
        $this->connection = $connection;

        // create browser instance
        $this->browser = new ProcessAwareBrowserExt($connection, $this);

        return $process;
    }
}
