<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\{SearchQuery, ParserTask};
use App\Services\ParserPool;
use Symfony\Component\Process\Process;
use App\Models\Settings;

class ParsersManagerCommand extends Command
{
    const NO_CHANGES_MIN = 5;
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
        //dump($runningTasks);
        $capacity = Settings::getBySlug('browser_process_count') ?? 1;
        $limit =  $capacity - $runningTasks;

        if ($limit <= 0) {
            Log::channel('daily')->debug('No place for tasks');
            return ;
        }

        $timeLimit = self::NO_CHANGES_MIN*60;

        // Прибить задачи, логи в которых не обновляются >NO_CHANGES_MIN  мин
        $tooLongExecuting = ParserTask::query()->where('status', 'active')
            ->whereNotIn('stage', ['new', 'done'])
            ->get()->filter(function ($item) use ($timeLimit) {
                $searchQueryId = $item->searchQuery->id;
                $logFileName = storage_path('logs/' . \App\Services\ParserService::LOG_PREFIX . $searchQueryId . '.log');
                if (file_exists($logFileName)) {

                    $lastActivityTime = filemtime($logFileName);
                    return (time() - $lastActivityTime >$timeLimit);
                }
                return false;
            });

        foreach ($tooLongExecuting as $tlItem) {
            Log::channel('daily')->debug('Too long executing: [ID:'.$tlItem->id.'] PID:'.$tlItem->process_pid);
        }

        $disabled =  ParserTask::query()->where('status', 'active')->whereHas('searchQuery', function ($builder) {
            return $builder->where('is_enabled', 0);
        })->get();

        foreach ($tooLongExecuting as $tlItem) {
            Log::channel('daily')->debug('Too long executing: [ID:'.$tlItem->id.'] PID:'.$tlItem->process_pid);
        }

        $idsToDelete = [];

        $toDelete = $tooLongExecuting->merge($disabled);

        foreach ($toDelete as $pTask) {
            Log::channel('daily')->debug('To delete: [ID:'.$pTask->id.'] PID:'.$pTask->process_pid);
            if ($pTask->process_pid>0) {
                //Run it with root privileges!
                shell_exec('kill -9 '.$pTask->process_pid);
                sleep(5);
                $out = shell_exec('ps -o pid -p '.$pTask->process_pid);
                $cleanedOut =  preg_replace("~PID|[\n\t\s]~", '',  $out);
                if (intval($cleanedOut)>0) {
                    Log::debug('Cannot kill process '.$pTask->process_pid);
                } else {
                    $idsToDelete[]= $pTask->id;
                }
            }
        }

        if (count($idsToDelete)> 0) {
            ParserTask::query()->whereIn('id', $idsToDelete)->delete();
        }

        // Получить уже запущенные задачи
        $runningTasks = ParserTask::query()->select('search_query_id')->whereIn('stage',['new', 'pages', 'ads'])
            ->whereNot('status', 'error')
            ->get()->pluck('search_query_id')->toArray();

        Log::channel('daily')->debug('Задач запущено:'.count($runningTasks));

        //Получить задачи, которые ещё не запущены
        $sqIds =  SearchQuery::query()->select('id')
            ->where('is_enabled', 1)
            ->where(fn ($builder) => $builder
                ->whereBetween('launch_time', [now()->format('H:i:s'), now()->add('1 hour')->format('H:i:s')])
                 ->orWhere(fn($builderOr) => $builderOr->whereNull('launch_time'))
            )
            ->whereNotIn('id', $runningTasks)
            ->orderByRaw('priority DESC, updated_at ASC')
            ->limit($limit)->get()->pluck('id')->toArray();

        Log::channel('daily')->debug('Tasks for run:'.print_r($sqIds, true));

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
            Log::channel('daily')->debug($cmd);
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
