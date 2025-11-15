<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            // ... any global web middleware you want to add
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run weather alerts at 6:00 AM Philippines time
        $schedule->command('weather:send-alerts')
                 ->dailyAt('06:00')
                 ->timezone('Asia/Manila')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Run weather alerts at 12:00 PM (noon) Philippines time
        $schedule->command('weather:send-alerts')
                 ->dailyAt('12:00')
                 ->timezone('Asia/Manila')
                 ->withoutOverlapping()
                 ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
