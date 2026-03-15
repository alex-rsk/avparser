<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
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
    protected $signature = 'parser:run {parser_task_id}';

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
        $ptId = $this->argument('parser_task_id');
        Log::channel('daily')->debug('PT ID '.$ptId);
        $parserTask = ParserTask::find($ptId);
        if (is_null($parserTask)) {
            Log::channel('daily')->debug('Parser task NF');
            return;
        }

        Log::channel('daily')->debug('Running task '.$ptId);
        $pool = ParserPool::getInstance();  
        $pool->addBrowserInstance($parserTask);

    }
}
