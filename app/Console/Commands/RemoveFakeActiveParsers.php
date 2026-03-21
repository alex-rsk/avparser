<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ParserTask;

class RemoveFakeActiveParsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:remove-fake-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удалить из таблицы те парсеры которые якобы активны';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeParsers = ParserTask::query()->where('status', 'active')->get();

        foreach ($activeParsers as $parserTask) {
            $cmd = 'ps h -o pid -p '.$parserTask->process_pid;
            $out = shell_exec($cmd);
            if (empty($out)) {
                Log::channel('daily')->warning('Process PID '.$parserTask->process_pid.' not found., process task ID: '.$parserTask->id);
                ParserTask::find($parserTask->id)->delete();
            }
        }
            
    }
}
