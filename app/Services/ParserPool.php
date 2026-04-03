<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\{ParserTask, Settings};
use App\Services\Exceptions\TooManyInstancesException;
use App\Services\ParserService;
use Symfony\Component\Process\Process;


class ParserPool
{
    private static array $tasks = [];

    private static $instance =  null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {

        //Актуалиазация запущенных экземпляров
   /*
        $parserTasks = ParserTask::query()->whereNot('status', 'stopped')
            ->where('process_pid', '>', 0)->get();
        foreach ($parserTasks as $parserTask) {
            if (!empty($parserTask->process_pid)) {
                $cmd = 'ps h -o pid -p '.$parserTask->process_pid;
                $out = shell_exec($cmd);;
                if (empty($out)) {
                    Log::channel('daily')->warning('Process PID '.$parserTask->process_pid.' not found., process task ID: '.$parserTask->id);
                    $parserTask->status = 'paused';
                    $parserTask->process_pid = 0;
                    $parserTask->save();
                }
            } else {
                Log::channel('daily')->warning('Process pid is empty, process task ID: '.$parserTask->id);
            }
        }
        */
    }


    public function getTasks() : array
    {
        return self::$tasks;
    }


    public function getActualProcessesCount()
    {
        $config = config('headless-chrome');
        $cmd = $config['browser_bin'];

        $browserBinPath = explode(DIRECTORY_SEPARATOR, $config['browser_bin']);
        $bin = end($browserBinPath);

        $browser = preg_match('#chromium|chrome|canary#si', $bin, $matches) ?
            strtolower($matches[0]) : false;

        if (!$browser){
            Log::channel('browser')->debug('Неправильное имя браузера: '.$bin);
            return false;
        }

        $browserGrep = '['.substr($browser,0,1).']'.substr($browser,1);
        //$checkCommand = 'ps -eaf | grep "'.$browserGrep.'.*thread-id" | awk \'$1 ~ /./ {print $2}\'';
        $cmdCount = 'ps -eaf | grep "'.$browserGrep.'.*thread-id" | awk \'$1 ~ /./ {print $2}\' | wc -l';
        $process = Process::fromShellCommandline($cmdCount);

        $process->run();
        if (!$process->isSuccessful()) {
            \Log::error('Process monitor failed: ' . $process->getErrorOutput());
            return 0;
        }

        return (int)trim($process->getOutput());
    }

    public static function killOrphanedProcesses()
    {
        $killed = 0;
        $chromeProcs = [];
        foreach (glob('/proc/[0-9]*/cmdline', GLOB_NOSORT) as $cmdline) {
            $pid = (int)explode('/', $cmdline)[2];
            $content = file_get_contents($cmdline);
            if (preg_match('#--user-data-dir=.*avito(\d+)#', str_replace("\0", ' ', $content), $m)) {
                $chromeProcs[$pid] = (int)$m[1];
            }
        }

        foreach ($chromeProcs as $pid => $taskId) {
            if (ParserTask::query()->where('search_query_id', $taskId)->count() == 0 )
            {
                $cmd = "kill -9 $pid";
                echo 'Kill '.$pid;
                shell_exec($cmd);
                $killed++;
            }
        }

        if ($killed > 0) {
            Log::channel('daily')->debug('Killed: '.$killed);
        } else {
            Log::channel('daily')->debug('Nothing to kill');
        }

    }


    public function addBrowserInstance(ParserTask $parserTask) : ?int
    {
        try {

            Log::channel('browser')->debug('Create new parser service');
            $ps = new ParserService($parserTask->searchQuery->id);
            $ps->run($parserTask);
            return $parserTask->process_pid;

            Cache::forever(self::TASKS_CACHE_KEY, $this->instances);
        }
        catch (\Exception $ex) {
            Log::channel('daily')->error(__CLASS__.': '.$ex->getMessage().' '.$ex->getTraceAsString());
            return null;
        }
    }


    public function removeBrowserInstance(ParserTask $parserTask)
    {
        if (!empty($parserTask->process_pid)) {
            $cmd = 'kill -9 '.$parserTask->process_pid;
            shell_exec($cmd);
            $parserTask->status = 'stopped';
            $parserTask->process_pid = 0;
            $parserTask->save();
        }

        if (isset($this->tasks[$parserTask->searchQuery->query_text])) {
            unset($this->tasks[$parserTask->searchQuery->query_text]);
        }
    }

}
