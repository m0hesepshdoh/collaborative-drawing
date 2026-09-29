<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DrawingSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SessionAiGenerationTest extends TestCase
{
    public function test_generated_image_is_saved_to_public_storage(): void
    {
        config(['services.sensenova.api_key' => 'test-key']);
        Storage::fake('public');
        Http::fake([
            'https://token.sensenova.ai/v1/images/edits' => Http::response([
                'data' => [['url' => 'https://cdn.sensenova.ai/generated.png']],
            ]),
            'https://cdn.sensenova.ai/generated.png' => Http::response('fake-png-bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

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
        AppSetting::current()->update(['ai_generation_enabled' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/session/ABC123/generate-ai', [
                'image_data_url' => 'data:image/png;base64,AA==',
                'prompt' => 'Polish this drawing.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $files = Storage::disk('public')->allFiles('ai-results/ABC123');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.png', $files[0]);
        $this->assertSame('fake-png-bytes', Storage::disk('public')->get($files[0]));
        $this->assertStringContainsString('/storage/ai-results/ABC123/', $session->fresh()->ai_image_url);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/images/edits')
            && $request['response_format'] === 'url'
            && $request['output_format'] === 'png');
    }
}