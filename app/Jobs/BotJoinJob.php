<?php

namespace App\Jobs;

use App\Events\BotJoined;
use App\Models\DrawingSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

class BotJoinJob implements ShouldQueue
{
    use FoundationQueueable;

    public function __construct(public int $sessionId) {}

    public function handle(): void
    {
        $s = DrawingSession::find($this->sessionId);
        if (! $s || $s->status === 'ended') {
            return;
        }$active = $s->players()->whereNull('left_at')->get();
        if ($active->where('is_bot', false)->count() === 1 && ! $active->contains('is_bot', true)) {
            $s->players()->create(['is_bot' => true, 'last_activity_at' => now(), 'joined_at' => now()]);
            $s->update(['status' => 'active']);
            event(new BotJoined($s->code));
            BotDrawJob::dispatch($s->id)->delay(now()->addSecond());
        }
    }
}
