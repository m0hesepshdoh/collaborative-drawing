<?php

namespace App\Http\Middleware;

use App\Models\Ban;
use Closure;
use Illuminate\Http\Request;

class CheckBannedIp
{
    public function handle(Request $r, Closure $next)
    {
        if (Ban::where('ip_address', $r->ip())->exists()) {
            return response()->view('banned', [], 403);
        }

return $next($r);
    }
}
