<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminIpMiddleware
{
    public function handle(Request $r, Closure $next)
    {
        abort_unless(in_array($r->ip(), config('admin.allowed_ips', []), true), 403, 'Unauthorized');

        return $next($r);
    }
}
