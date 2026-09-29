<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SessionController;
use App\Jobs\BotJoinJob;
use App\Models\AppSetting;
use App\Models\DrawingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    public function test_admin_settings_persist_ai_and_wait_durations(): void
    {
        $request = Request::create('/admin/settings', 'POST', [
            'bot_wait_seconds' => 45,
            'finish_wait_seconds' => 90,
            'ai_generation_enabled' => '1',
        ]);

        (new AdminController)->updateSettings($request);

        $settings = AppSetting::current();
        $this->assertTrue($settings->ai_generation_enabled);
        $this->assertSame(45, $settings->bot_wait_seconds);
        $this->assertSame(90, $settings->finish_wait_seconds);
    }

    public function test_generate_ai_is_rejected_when_admin_turns_it_off(): void
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
        AppSetting::current()->update(['ai_generation_enabled' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/session/ABC123/generate-ai', ['image_data_url' => 'data:image/png;base64,AA=='])
            ->assertStatus(409)
            ->assertJsonPath('message', 'AI image generation is disabled by the administrator.');
    }

    public function test_configured_bot_wait_is_used_when_scheduling_bot_fallback(): void
    {
        AppSetting::current()->update(['bot_wait_seconds' => 45]);
        Bus::fake();
        $request = Request::create('/session/create', 'POST', [
            'bot_after_timeout' => '1',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        (new SessionController)->create($request);

        Bus::assertDispatched(BotJoinJob::class, function (BotJoinJob $job): bool {
            return $job->delay instanceof \DateTimeInterface
                && abs($job->delay->getTimestamp() - now()->addSeconds(45)->getTimestamp()) <= 1;
        });
    }
}