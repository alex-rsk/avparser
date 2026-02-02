<?php 

namespace App\Services;

use Illuminate\Support\Facades\Cache;


class ParserPool 
{
    const TASKS_CACHE_KEY = 'parser_tasks';    

    private array $instances = [];

    public function __construct()
    {
         $this->instances = Cache::get(self::TASKS_CACHE_KEY) ?? [];
    }

    private function getActualProcessesCount()
    {
        $cmdCount = 'ps -eaf | grep "'.$browserGrep.'.*thread-id-'.$instanceNumber.'" | awk \'$1 ~ /./ {print $2}\' | wc -l';
        $output =  shell_exec($cmdCount);
        if ($output) {
            return (int) $output;
        }
        return 0;
    }

    public function addTask(string $searchQuery)
    {
        $instCount = $this->getActuallProcessesCount();
        
        $this->instances[] = [
            'query'  => $searchQuery,
            'status' => 'new',
            'pid'    => 0,
            'thread_id' => $instCount + 1
        ];

        Cache::forever(self::TASKS_CACHE_KEY, $this->instances);
    }

    
}