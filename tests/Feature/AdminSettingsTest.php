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
    public function test_allow_listed_admin_can_authorize_an_active_session_channel(): void
    {
        config(['admin.allowed_ips' => ['127.0.0.1']]);
        DrawingSession::create([
            'code' => 'ABC123',
            'background_id' => null,
            'status' => 'active',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withSession(['admin_authenticated' => true])
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-session.ABC123',
                'socket_id' => '123.456',
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_admin_outside_the_allow_list_cannot_authorize_a_session_channel(): void
    {
        config(['admin.allowed_ips' => []]);
        DrawingSession::create([
            'code' => 'ABC123',
            'background_id' => null,
            'status' => 'active',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withSession(['admin_authenticated' => true])
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-session.ABC123',
                'socket_id' => '123.456',
            ])
            ->assertForbidden();
    }

    public function test_sessions_dashboard_renders_a_live_preview_for_each_active_session(): void
    {
        DrawingSession::create([
            'code' => 'ABC123',
            'background_id' => null,
            'status' => 'active',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withSession(['admin_authenticated' => true])
            ->get('/admin-panel-xyz')
            ->assertOk()
            ->assertSee('data-session-preview="ABC123"', false)
            ->assertSee('js/admin-session-previews.js');
    }

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