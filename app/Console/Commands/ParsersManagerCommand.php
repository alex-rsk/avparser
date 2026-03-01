<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\{SearchQuery, ParserTask};
use App\Services\ParserPool;
use Symfony\Component\Process\Process;

class ParsersManagerCommand extends Command
{
    const EXECUTING_TOO_LONG_MIN = 1440;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pool = ParserPool::getInstance();
        $runningTasks = $pool->getActualProcessesCount();
        $capacity = config('headless-chrome.max_instances');
        $limit = 1;//$capacity - $runningTasks;

        if ($limit <= 0) {
            Log::channel('daily')->debug('No place for tasks');
            return ;
        }
        // Прибить задачи, которые зависли в промежуточном статусе если они выполняются слишком долго        
        $tooLongExecuting = ParserTask::query()
            ->where('status', 'active')
            ->whereNotIn('stage', ['new', 'done'])
            ->whereRaw("DATEDIFF('".date('Y-m-d H:i:s')."', updated_at) < ".self::EXECUTING_TOO_LONG_MIN)->get();


        // 1. Получить PID
        // 2. Прибить задачу (этот крон должен быть запущен с рут-привилегиями)
        $idsToDelete = [];
        foreach ($tooLongExecuting as $tl) {
            Log::channel('daily')->debug('Too long executing: [ID:'.$tl->id.'] PID:'.$tl->process_pid);
            if ($tl->process_pid>0) {
                //Run it with root privileges!
                shell_exec('kill -9 '.$tl->process_pid);
                sleep(5);
                $out = shell_exec('ps -o pid -p '.$tl->process_pid);
                $cleanedOut =  preg_replace("~PID|[\n\t\s]~", '',  $out);
                if (intval($cleanedOut)>0) {
                    Log::debug('Cannot kill process '.$tl->process_pid);
                } else {
                    $idsToDelete[]= $tl->id;
                }
            }
        }

        if (count($idsToDelete)> 0) {
            ParserTask::whereIn('id', $idsToDelete)->update(['status' => 'error']); 
        }

        // Получить уже запущенные задачи
        $runningTasks = ParserTask::query()->select('search_query_id')->where('stage','done')
            ->get()->pluck('search_query_id')->toArray();

        Log::channel('daily')->debug('Задач запущено:'.count($runningTasks));

        //Получить задачи, которые ещё не запущены
        $sqIds =  SearchQuery::query()->select('id')->whereNotIn('id', $runningTasks)
            ->limit($limit)->get()->pluck('id')->toArray();
            
        if (empty($sqIds)) {
            Log::channel('daily')->debug("No search queries");
            return;
        }

        //dump($sqIds);

        //Запустить задачи
        foreach ($sqIds as $sqId) {
            $oldDoneTask = ParserTask::query()->where('search_query_id' , $sqId)->where('status', 'active')
                ->where('stage', 'done')->first();

            if (is_null($oldDoneTask)) {
                $pTask  = ParserTask::create([
                    'search_query_id' => $sqId,
                    'status'        => 'active',
                    'title'         => 'Task '.$sqId,
                    'stage'         => 'new',
                    'process_pid'   => 0,
                    'priority'      => 1
                ]);
            } else {
                Log::channel('daily')->debug("Revive the old task of id $sqId");
                $pTask = $oldDoneTask;
                $pTask->stage = 'new';
                $pTask->save();
            }
            
            dump('Starting process');

            
            $cmd = 'nohup php artisan parser:run '.$pTask->id.' > /dev/null 2>/dev/null & echo $!';
            //$process = Process::fromShellCommandLine('nohup php artisan parser:run '.$pTask->id.' > /dev/null 2>/dev/null &');
            //$process->setTimeout(3600);
            //$process->start();
            $pid = shell_exec($cmd);
            dump($pid);
            
            dump('Started');
        
            //Записать PID после запуска
            $pTask->process_pid = $pid;
            $pTask->save();
            Log::channel('daily')->debug('Started for search query '.$sqId);
            sleep(2);
            //$process->wait();
        }
    }
}
