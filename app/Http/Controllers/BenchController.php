<?php

// Provides benchmark-only clock synchronization.
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BenchController extends Controller
{
    // Returns the server clock to an authorized benchmark client.
    public function timestamp(Request $request)
    {
        $secret = (string) config('services.bench_secret');

        abort_unless($secret !== '' && hash_equals($secret, (string) $request->header('X-Bench-Secret')), 403);

        return response()->json(['server_time_ms' => round(microtime(true) * 1000)]);
    }
}