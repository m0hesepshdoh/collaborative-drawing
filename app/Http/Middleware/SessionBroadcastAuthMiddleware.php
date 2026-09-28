<?php

namespace App\Http\Middleware;

use App\Models\DrawingSession;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionBroadcastAuthMiddleware
{
    public function handle(Request $r, Closure $next)
    {
        $channel = (string) $r->input('channel_name');
        if (! preg_match('/^private-session\\.([A-Z0-9]{6})$/', $channel, $m)) {
            abort(403);
        } $ok = DrawingSession::where('code', $m[1])->whereHas('players', fn ($q) => $q->where('ip_address', $r->ip())->whereNull('left_at'))->exists();
        abort_unless($ok, 403);
        Auth::setUser(new GenericUser(['id' => $r->ip(), 'ip_address' => $r->ip()]));

        return $next($r);
    }
}
