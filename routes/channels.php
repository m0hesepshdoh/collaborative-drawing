<?php

use App\Models\DrawingSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('session.{code}', function ($user, string $code) {
    if (! isset($user->ip_address)) {
        return false;
    }

    return DrawingSession::where('code', strtoupper($code))->whereHas('players', fn ($q) => $q->where('ip_address', $user->ip_address)->whereNull('left_at'))->exists();
});
