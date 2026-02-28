<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'Xss' => \App\Http\Middleware\XssMiddleware::class,
            'client' => \App\Http\Middleware\CheckClientCredentialsMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('activitylogs:delete')->dailyAt('00:01');
        $schedule->command('tree:remove-non-latest')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
