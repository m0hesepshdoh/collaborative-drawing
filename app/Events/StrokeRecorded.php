<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StrokeRecorded implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

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
        return $this->stroke;
    }
}