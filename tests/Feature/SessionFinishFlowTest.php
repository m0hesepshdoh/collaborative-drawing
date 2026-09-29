<?php

namespace Tests\Feature;

use App\Http\Controllers\SessionController;
use App\Models\AppSetting;
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

    public function test_state_endpoint_finalizes_an_expired_waiting_session(): void
    {
        $session = DrawingSession::create([
            'code' => 'DEF456',
            'background_id' => null,
            'status' => 'active',
            'finish_state' => 'waiting',
            'finished_by_ip' => '127.0.0.1',
            'finish_deadline_at' => now()->subSecond(),
        ]);
        $session->players()->create([
            'ip_address' => '127.0.0.1',
            'is_bot' => false,
            'last_activity_at' => now(),
            'joined_at' => now(),
        ]);

        $request = Request::create('/session/DEF456/state', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $response = (new SessionController)->state($request, 'DEF456');

        $this->assertSame('finalized', $response->getData()->state);
        $this->assertTrue($response->getData()->canGenerateAi);
        $this->assertNotNull($session->fresh()->finished_at);
    }

    public function test_finish_deadline_uses_admin_configured_duration_and_ai_setting(): void
    {
        $session = DrawingSession::create([
            'code' => 'GHI789',
            'background_id' => null,
            'status' => 'active',
        ]);
        $session->players()->create([
            'ip_address' => '127.0.0.1',
            'is_bot' => false,
            'last_activity_at' => now(),
            'joined_at' => now(),
        ]);
        AppSetting::current()->update([
            'ai_generation_enabled' => false,
            'finish_wait_seconds' => 30,
        ]);

        $request = Request::create('/session/GHI789/finish', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $response = (new SessionController)->finish($request, 'GHI789');

        $this->assertSame(30, $response->getData()->remainingSeconds);
        $this->assertFalse($response->getData()->aiEnabled);
        $this->assertGreaterThan(29, now()->diffInSeconds($session->fresh()->finish_deadline_at, false));
    }
}
