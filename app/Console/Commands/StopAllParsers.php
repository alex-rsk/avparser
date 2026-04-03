<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParserTask;
use App\Services\ParserPool;

class StopAllParsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stop-all-parsers';

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
        $pool = new ParserPool();
        $parsers = ParserTask::query()->get();

        foreach ($parsers as $parserTask) {
            $pool->removeBrowserInstance($parserTask);
        }
    }
}
