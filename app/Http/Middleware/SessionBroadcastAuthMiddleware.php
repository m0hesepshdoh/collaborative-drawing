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
        }

        $isAdmin = $r->session()->get('admin_authenticated')
            && in_array($r->ip(), config('admin.allowed_ips', []), true);
        $isActiveSession = DrawingSession::where('code', $m[1])
            ->whereIn('status', ['waiting', 'active'])
            ->exists();
        $isActivePlayer = DrawingSession::where('code', $m[1])
            ->whereHas('players', fn ($q) => $q->where('ip_address', $r->ip())->whereNull('left_at'))
            ->exists();

        abort_unless(($isAdmin && $isActiveSession) || $isActivePlayer, 403);
        Auth::setUser(new GenericUser([
            'id' => $r->ip(),
            'ip_address' => $r->ip(),
            'is_admin' => (bool) $isAdmin,
        ]));

        return $next($r);
    }
}
