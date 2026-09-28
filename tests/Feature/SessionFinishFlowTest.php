<?php

namespace Tests\Feature;

use App\Http\Controllers\SessionController;
use App\Models\DrawingSession;
use Illuminate\Http\Request;
use Tests\TestCase;

class SessionFinishFlowTest extends TestCase
{
    public function test_first_player_can_wait_and_second_player_can_finalize(): void
    {
        $session = DrawingSession::create([
            'code' => 'ABC123',
            'background_id' => null,
            'status' => 'active',
        ]);

        $session->players()->create([
            'ip_address' => '127.0.0.1',
            'is_bot' => false,
            'last_activity_at' => now(),
            'joined_at' => now(),
        ]);

        $session->players()->create([
            'ip_address' => '127.0.0.2',
            'is_bot' => false,
            'last_activity_at' => now(),
            'joined_at' => now(),
        ]);

        $controller = new SessionController();

        $firstRequest = Request::create('/session/ABC123/finish', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $first = $controller->finish($firstRequest, 'ABC123');
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame('waiting', $first->getData()->state);

        $secondRequest = Request::create('/session/ABC123/finish', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.2']);
        $second = $controller->finish($secondRequest, 'ABC123');
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame('finalized', $second->getData()->state);

        $session->refresh();
        $this->assertNotNull($session->finished_at);
        $this->assertSame('127.0.0.2', $session->finished_by_ip);
    }
}
