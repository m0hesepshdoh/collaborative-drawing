<?php

namespace App\Jobs;

use App\Events\StrokeRecorded;
use App\Models\BotPattern;
use App\Models\DrawingSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BotDrawJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 120;

    public function __construct(public int $sessionId) {}

    public function handle(): void
    {
        $s = DrawingSession::find($this->sessionId);
        if (! $s || $s->status !== 'active' || ! $s->players()->whereNull('left_at')->where('is_bot', true)->exists()) {
            return;
        }$bot = $s->players()->whereNull('left_at')->where('is_bot', true)->first();
        $p = BotPattern::where('approved', true)->inRandomOrder()->first();
        if ($p) {
            foreach ($p->strokes as $stroke) {
                $s->refresh();
                if ($s->status !== 'active') {
                    return;
                }$recorded = $s->recordedStrokes()->create([
                    'player_ip' => $bot?->ip_address ?? 'bot',
                    'points' => $stroke['points'],
                    'color' => $stroke['color'],
                    'size' => (int) $stroke['size'],
                    'drawn_at' => now(),
                ]);
                event(new StrokeRecorded($s->code, $recorded->only(['id', 'points', 'color', 'size'])));
                usleep(((int) ($stroke['delay_ms'] ?? 200) + random_int(-50, 80)) * 1000);
            }
        } else {
            $points = [];
            $x = random_int(50, 750);
            $y = random_int(50, 550);
            for ($i = 0; $i < 10; $i++) {
                $points[] = ['x' => $x + random_int(-60, 60), 'y' => $y + random_int(-60, 60)];
            }$recorded = $s->recordedStrokes()->create([
                'player_ip' => $bot?->ip_address ?? 'bot',
                'points' => $points,
                'color' => sprintf('#%06X', random_int(0, 0xFFFFFF)),
                'size' => [2, 5, 10, 20][array_rand([2, 5, 10, 20])],
                'drawn_at' => now(),
            ]);
            event(new StrokeRecorded($s->code, $recorded->only(['id', 'points', 'color', 'size'])));
        }self::dispatch($s->id)->delay(now()->addSeconds(random_int(1,3)));
    }
}
