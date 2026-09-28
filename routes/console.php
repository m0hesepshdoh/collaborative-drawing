<?php
use App\Events\PlayerKicked;
use App\Models\Player;
use Illuminate\Support\Facades\Schedule;
Schedule::call(function () {
    Player::with('session')->where('is_bot', false)->whereNull('left_at')->where('last_activity_at', '<', now()->subMinutes(3))->chunkById(100, function ($players) {
        foreach ($players as $player) {
            $session = $player->session;
            if (!$session) continue;
            event(new PlayerKicked($session->code, $player->ip_address));
            $player->update(['left_at' => now()]);
            if (!$session->players()->whereNull('left_at')->exists()) $session->update(['status' => 'ended']);
        }
    });
    \App\Models\DrawingSession::where('status','ended')->where('updated_at','<',now()->subMinutes(5))->delete();
})->everyMinute();
