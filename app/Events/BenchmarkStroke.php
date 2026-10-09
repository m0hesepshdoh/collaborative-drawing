<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BenchmarkStroke implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(public string $code, public array $data) {}

    public function broadcastOn(): array
    {
        return ["private-session.{$this->code}"];
    }

    public function broadcastAs(): string
    {
        return "client-stroke";
    }
}
