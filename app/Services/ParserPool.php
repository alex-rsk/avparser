<?php 

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\{ParserTask, Settings};
use App\Services\Exceptions\TooManyInstancesException;


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
                    $this->tasks[$parserTask->searchQuery->query_text] = $parserTask;
                }
            } else {
                Log::channel('daily')->warning('Process pid is empty, process task ID: '.$parserTask->id);
            }
        }
        
    }

    
    private function getActualProcessesCount()
    {
        $cmdCount = 'ps -eaf | grep "'.$browserGrep.'.*thread-id" | awk \'$1 ~ /./ {print $2}\' | wc -l';
        $output =  shell_exec($cmdCount);
        if ($output) {
            return (int) $output;
        }
        return 0;
    }

    public function addBrowserInstance(ParserTask $parserTask) : ?int
    {
        try {
            $instCount = $this->getActuallProcessesCount();

            $maxInstances = Settings::getBySlug('browser_process_count') ?? config('headless-chrome.max_instances');

            if ($instCount >= $maxInstances) {
                throw new TooManyInstancesException();
            }
            
            if (isset($this->tasks[$parserTask->searchQuery->query_text])) {
                return $parserTask->process_pid;
            }

            if ($parserTask->status == 'active') {
                
            }
            $this->tasks[$parserTask->searchQuery->query_text] = $parserTask;

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