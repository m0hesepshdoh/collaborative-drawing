<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoined implements ShouldBroadcastNow
{
    use Dispatchable,SerializesModels;

    public function __construct(public string $code, public ?string $ip) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.'.$this->code)];
    }

    public function broadcastAs(): string
    {
        return 'player.joined';
    }
}
