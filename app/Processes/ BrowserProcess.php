<?php
namespace App\Processes;

use App\Tasks\TestTask;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;

class BrowserProcess implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        // The callback method cannot exit. Once exited, Manager process will automatically create the process 
        while (!self::$quit) {
            \Log::info('Test process: running');
            // sleep(1); // Swoole < 2.1
            Coroutine::sleep(1); // Swoole>=2.1: Coroutine & Runtime will be automatically enabled for callback(). 
            // Pay attention to the compatibility between the components used and the coroutines. 
            // If they are not compatible, only some coroutines can be enabled, 
            // such as: \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_TCP | SWOOLE_HOOK_SLEEP | SWOOLE_HOOK_FILE);
             // Deliver task in custom process, but NOT support callback finish() of task.
            // Note: Modify task_ipc_mode to 1 or 2 in config/laravels.php, see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
            $ret = Task::deliver(new TestTask('task data'));
            var_dump($ret);
            // The upper layer will catch the exception thrown in the callback and record it in the Swoole log,
            //  and then this process will exit. 
            // The Manager process will re-create the process after 3 seconds, so developers need to try/catch to catch the exception 
            // by themselves to avoid frequent process creation.
            // throw new \Exception('an exception');
        }
    }
    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        \Log::info('Test process: reloading');
        self::$quit = true;
        // $process->exit(0); // Force exit process
    }
    // Requirements: LaravelS >= v3.7.4 & callback() must be async non-blocking program.
    public static function onStop(Server $swoole, Process $process)
    {
        // Stop the process...
        // Then end process
        \Log::info('Test process: stopping');
        self::$quit = true;
        // $process->exit(0); // Force exit process
    }
}