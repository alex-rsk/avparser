<?php
/**
 * Расширение класса BrowserFactory
 * 
 * @author Alex Ryassky 
 * @copyright (c) 2020, M1 <m1-shop.ru> 
 */
namespace Services\HeadlessBrowser;

use Apix\Log\Logger\Stream as StreamLogger;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Browser\ProcessAwareBrowser;
use Components\GHC\ProcessAwareBrowserExt;
use Components\GHC\BrowserProcessExt;
use HeadlessChromium\Communication\Connection;
use HeadlessChromium\Exception\BrowserConnectionFailed;

class BrowserFactoryExt extends BrowserFactory
{

    protected $chromeBinary;

    
    protected $process;
    
    /**
     * Стартует процесс хрома
     *
     * Опции запуска см. в \HeadlessChromium\BrowserFactory::createBrowser
     * 
     * @return ProcessAwareBrowser a Browser instance to interact with the new chrome process
     */
    public function createBrowserExt(array $options = []): ProcessAwareBrowserExt
    {

        $logger = $options['debugLogger'] ?? null;
        
        if (is_string($logger) || is_resource($logger)) {
            $logger = new StreamLogger($logger);
        }       

        // log chrome version
        if ($logger) {
            $chromeVersion = $this->getChromeVersion();
            $logger->debug('Factory: chrome version: ' . $chromeVersion);
        }

        // create browser process
        $browserProcess = new BrowserProcessExt($logger);

        // instruct the runtime to kill chrome and clean temp files on exit
        if (!array_key_exists('keepAlive', $options) || !$options['keepAlive']) {
            register_shutdown_function([$browserProcess, 'kill']);
        }

        // start the browser and connect to it
        $this->process = $browserProcess->start($this->chromeBinary, $options);

        return $browserProcess->getBrowser();
    }
    
     /**
     * Connects to an existing browser using it's web socket uri.
     *
     * usage:
     *
     * ```
     * $browserFactory = new BrowserFactory();
     * $browser = $browserFactory->createBrowser();
     *
     * $uri = $browser->getSocketUri();
     *
     * $existingBrowser = BrowserFactory::connectToBrowser($uri);
     * ```
     *
     * @param string $uri
     * @param array $options options when creating the connection to the browser:
     *  - connectionDelay: amount of time in seconds to slows down connection for debugging purposes (default: none)
     *  - debugLogger: resource string ("php://stdout"), resource or psr-3 logger instance (default: none)
     *  - sendSyncDefaultTimeout: maximum time in ms to wait for synchronous messages to send (default 3000 ms)
     *
     * @return Browser
     * @throws BrowserConnectionFailed
     */
    public static function connectToBrowserExt(string $uri, array $options = []): BrowserExt
    {
        $logger = $options['debugLogger'] ?? null;
        
        if (is_string($logger) || is_resource($logger)) {
            $logger = new StreamLogger($logger);
        }

        if ($logger) {
            $logger->debug('Browser Factory: connecting using ' . $uri);
        }

        // connect to browser
        $connection = new Connection($uri, $logger, $options['sendSyncDefaultTimeout'] ?? 3000);

        // try to connect
        try {
            $connection->connect();
        } catch (HandshakeException $e) {
            throw new BrowserConnectionFailed('Invalid socket uri', 0, $e);
        }

        // make sure it is connected
        if (!$connection->isConnected()) {
            throw new BrowserConnectionFailed('Cannot connect to the browser, make sure it was not closed');
        }

        // connection delay
        if (array_key_exists('connectionDelay', $options)) {
            $connection->setConnectionDelay($options['connectionDelay']);
        }

        return new BrowserExt($connection);
    }
    
    public function getBrowserProcess()
    {
        return $this->process;
    }
  
}
