<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Pay/A — крипто-платежи. Раз в 30 сек скан blockchain, матч
        // pending → confirmed. Задача без очереди (короткая, критичная,
        // одна за раз). withoutOverlapping чтобы не гнать параллельно
        // если предыдущий запуск ещё не завершился.
        $schedule->command('payments:check-pending')
                 ->everyThirtySeconds()
                 ->withoutOverlapping(2)
                 ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
