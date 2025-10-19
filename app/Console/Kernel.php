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
        // Run weather alerts every hour
        $schedule->command('weather:send-alerts')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Alternative: Run weather alerts daily at 8:00 AM
        // $schedule->command('weather:send-alerts')
        //          ->dailyAt('08:00')
        //          ->withoutOverlapping();

        // Alternative: Run weather alerts every 6 hours
        // $schedule->command('weather:send-alerts')
        //          ->everySixHours()
        //          ->withoutOverlapping();
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
