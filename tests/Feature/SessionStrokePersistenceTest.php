<?php

namespace Tests\Feature;

use App\Events\StrokeRecorded;
use App\Http\Controllers\SessionController;
use App\Models\DrawingSession;
use App\Models\RecordedStroke;
use Illuminate\Http\Request;
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
}