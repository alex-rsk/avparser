<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ad;

class ClearErrorPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-error-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove erroneous pages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ads = Ad::query()->select('id')->where('status', 'error')->get()->pluck('id')->toArray();
        $chunks = array_chunk($ads, 100);
        foreach ($chunks as $chunk) {
            Ad::destroy($chunk);
        }                        
    }
}
