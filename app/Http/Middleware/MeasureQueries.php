<?php

// Logs database query count and timing only when the local measurement environment is enabled.
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasureQueries
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! (config('app.env') === 'local' && config('app.measure_queries', false))) {
            return $next($request);
        }
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query;
        });
        $response = $next($request);
        $total = 0.0;
        $slowest = 0.0;
        foreach ($queries as $query) {
            $duration = (float) $query->time * 1000;
            $total += $duration;
            $slowest = max($slowest, $duration);
        }
        $line = json_encode([
            'route' => $request->path(),
            'queries' => count($queries),
            'total_ms' => round($total, 3),
            'slowest_ms' => round($slowest, 3),
        ]).PHP_EOL;
        $handle = fopen(storage_path('logs/queries.log'), 'ab');
        fwrite($handle, $line);
        fclose($handle);
        return $response;
    }
}
