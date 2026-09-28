<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuthMiddleware
{
    public function handle(Request $r, Closure $next)
    {
        return $r->session()->get('admin_authenticated') ? $next($r) : redirect()->route('admin.login');
    }
}
