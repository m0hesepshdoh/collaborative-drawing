<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StrokeDrawnForBot implements ShouldBroadcastNow
{
    use Dispatchable,SerializesModels;

    public function __construct(public string $code, public array $points, public string $color, public int $size) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.'.$this->code)];
    }

    public function broadcastAs(): string
    {
        return 'bot.stroke';
    }
}
