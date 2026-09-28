<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StrokeRecorded implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    private const MAX_BROADCAST_POINTS = 128;

    public function __construct(public string $code, public array $stroke) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.'.$this->code)];
    }

    public function broadcastAs(): string
    {
        return 'stroke.recorded';
    }

    public function broadcastWith(): array
    {
        $points = $this->stroke['points'];
        $pointCount = count($points);

        if ($pointCount > self::MAX_BROADCAST_POINTS) {
            $points = array_map(
                fn (int $index): array => $points[(int) round($index * ($pointCount - 1) / (self::MAX_BROADCAST_POINTS - 1))],
                range(0, self::MAX_BROADCAST_POINTS - 1),
            );
        }

        $points = array_map(fn (array $point): array => [
            'x' => round((float) $point['x'], 1),
            'y' => round((float) $point['y'], 1),
        ], $points);

        return [...$this->stroke, 'points' => $points];
    }
}