<?php 

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\{ParserTask, Settings};
use App\Services\Exceptions\TooManyInstancesException;
use App\Services\ParserService;


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
        $parserTasks = ParserTask::query()->whereNot('status', 'stopped')->get();
        foreach ($parserTasks as $parserTask) {
            if (!empty($parserTask->process_pid)) {
                $cmd = 'ps h -o pid -p '.$parserTask->process_pid;
                $out = shell_exec($cmd);;
                if (empty($out)) {
                    Log::channel('daily')->warning('Process PID '.$parserTask->process_pid.' not found., process task ID: '.$parserTask->id);
                    $parserTask->status = 'paused';
                    $parserTask->process_pid = 0;
                    $parserTask->save();
                } else {
                    self::$tasks[$parserTask->searchQuery->query_text] = $parserTask;
                }
            } else {
                Log::channel('daily')->warning('Process pid is empty, process task ID: '.$parserTask->id);
            }
        }
        
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
        exec($cmdCount, $output);
        if ($output) {
            return (int) $output;
        }
        return 0;
    }

    public function addBrowserInstance(ParserTask $parserTask) : ?int
    {
        try {
            $instCount = $this->getActualProcessesCount();

            $maxInstances = Settings::getBySlug('browser_process_count') ?? config('headless-chrome.max_instances');

            if ($instCount >= $maxInstances) {
                throw new TooManyInstancesException();
            }
            
            if (isset(self::$tasks[$parserTask->searchQuery->query_text])) {
                return $parserTask->process_pid;
            }

            if ($parserTask->status == 'active') {

            }

            self::$tasks[$parserTask->searchQuery->query_text] = $parserTask;

            $ps = new ParserService();
            $ps->run($parserTask);
            return $parserTask->process_pid;

            Cache::forever(self::TASKS_CACHE_KEY, $this->instances);
        }
        catch (\Exception $ex) {
            Log::channel('daily')->error(__CLASS__.': '.$ex->getMessage());
            return null;
        }
    }


    public function removeBrowserInstance(ParserTask $parserTask)
    {
        if (isset($this->tasks[$parserTask->searchQuery->query_text])) {
            $pt = $this->tasks[$parserTask->searchQuery->query_text];
            if (!$pt instanceof ParserTask) {
                throw new \Exception('Not a ParserTask object');   
            }
            $cmd = 'kill -9 '.$pt->process_pid;
            shell_exec($cmd);
            $pt->status = 'stopped';
            $pt->process_pid = 0;
            $pt->save();
            unset($this->tasks[$parserTask->searchQuery->query_text]);
        }
        
    }
    
}