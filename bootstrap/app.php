<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/benchmark.php', apiPrefix: '', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['benchmark/*', 'bench/*']);
        $middleware->alias([
            'admin.ip' => \App\Http\Middleware\AdminIpMiddleware::class,
            'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
            'not.banned' => \App\Http\Middleware\CheckBannedIp::class,
            'session.broadcast' => \App\Http\Middleware\SessionBroadcastAuthMiddleware::class,
            'measure.queries' => \App\Http\Middleware\MeasureQueries::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\MeasureQueries::class);
    })
    ->withExceptions(fn (Exceptions $exceptions) => null)
    ->create();
