<?php

use App\Models\DrawingSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('session.{code}', function ($user, string $code) {
    if (! isset($user->ip_address)) {
        return false;
    }

    if (($user->is_admin ?? false) && in_array($user->ip_address, config('admin.allowed_ips', []), true)) {
        return DrawingSession::where('code', strtoupper($code))
            ->whereIn('status', ['waiting', 'active'])
            ->exists();
    }

    return DrawingSession::where('code', strtoupper($code))->whereHas('players', fn ($q) => $q->where('ip_address', $user->ip_address)->whereNull('left_at'))->exists();
});
