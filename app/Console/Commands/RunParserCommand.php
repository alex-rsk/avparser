<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{SearchQuery, ParserTask};
use App\Services\ParserPool;

class RunParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:run';

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

        dump('Задач запушено '.$runningTasks);

        $pTask = ParserTask::first();
        $pool->addBrowserInstance($pTask);

    }
}
