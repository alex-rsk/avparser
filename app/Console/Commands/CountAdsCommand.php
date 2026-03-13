<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Ad,AdView,AdReview, ParserTask};

class CountAdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:counts';

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
        $fileName = 'stats.csv';

        $countTasks = ParserTask::query()->count();
        $countAds = Ad::query()->count();
        $countAdViews = AdView::query()->count();
        $content = "$countTasks, $countAds, $countAdViews";
        file_put_contents(storage_path('logs/stats.csv'), $content, \FILE_APPEND);
        //$countAds = 
    }
}
