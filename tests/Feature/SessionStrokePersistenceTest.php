<?php

namespace Tests\Feature;

use App\Events\StrokeRecorded;
use App\Http\Controllers\SessionController;
use App\Models\DrawingSession;
use App\Models\RecordedStroke;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SessionStrokePersistenceTest extends TestCase
{
    public function test_completed_stroke_is_saved_and_broadcast_from_the_backend(): void
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
        Event::fake();

        $request = Request::create('/session/ABC123/record-stroke', 'POST', [
            'points' => [['x' => 15, 'y' => 25], ['x' => 30, 'y' => 45]],
            'color' => '#123456',
            'size' => 5,
            'client_stroke_id' => 'd9428888-122b-4a3e-9e3d-2c1d004f0f21',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $response = (new SessionController)->recordStroke($request, 'ABC123');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, RecordedStroke::where('session_id', $session->id)->count());
        Event::assertDispatched(StrokeRecorded::class, function (StrokeRecorded $event): bool {
            return $event->code === 'ABC123'
                && $event->stroke['points'][0]['x'] === 15
                && $event->stroke['client_stroke_id'] === 'd9428888-122b-4a3e-9e3d-2c1d004f0f21';
        });
    }

    public function test_broadcast_stroke_payload_is_bounded(): void
    {
        $points = [];
        for ($index = 0; $index < 1000; $index++) {
            $points[] = ['x' => $index * 0.123456, 'y' => $index * 0.654321];
        }
        $event = new StrokeRecorded('ABC123', [
            'id' => 1,
            'points' => $points,
            'color' => '#123456',
            'size' => 5,
            'client_stroke_id' => 'stroke-1',
        ]);
        $payload = $event->broadcastWith();

        $this->assertCount(1000, $event->stroke['points']);
        $this->assertCount(128, $payload['points']);
        $this->assertLessThan(10_000, strlen(json_encode($payload)));
        $this->assertEquals(0, $payload['points'][0]['x']);
        $this->assertSame(round(999 * 0.123456, 1), $payload['points'][127]['x']);
    }

    public function test_bot_fallback_is_only_scheduled_when_requested(): void
    {
        Bus::fake();
        $controller = new SessionController();
        $request = Request::create('/session/create', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $controller->create($request);

        Bus::assertNotDispatched(\App\Jobs\BotJoinJob::class);

        $requestWithBot = Request::create('/session/create', 'POST', ['bot_after_timeout' => '1'], [], [], ['REMOTE_ADDR' => '127.0.0.2']);
        $controller->create($requestWithBot);

        Bus::assertDispatched(\App\Jobs\BotJoinJob::class);
    }
}