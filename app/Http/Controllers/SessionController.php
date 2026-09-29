<?php

namespace App\Http\Controllers;

use App\Events\BotJoined;
use App\Events\CanvasCleared;
use App\Events\PlayerJoined;
use App\Events\PlayerLeft;
use App\Events\StrokeRecorded;
use App\Jobs\BotDrawJob;
use App\Jobs\BotJoinJob;
use App\Models\AppSetting;
use App\Models\Background;
use App\Models\DrawingSession;
use App\Models\Player;
use App\Models\RecordedStroke;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    private function newCode(): string
    {
        do {
            $c = strtoupper(Str::random(6));
        } while (DrawingSession::where('code', $c)->exists());

        return $c;
    }

    private function activeHuman(DrawingSession $s, string $ip): ?Player
    {
        return $s->players()->where('ip_address', $ip)->where('is_bot', false)->whereNull('left_at')->first();
    }

    public function create(Request $r)
    {
        $data = $r->validate(['bot_after_timeout' => ['sometimes', 'boolean']]);
        $s = DB::transaction(function () use ($r) {
            $s = DrawingSession::create(['code' => $this->newCode(), 'background_id' => Background::inRandomOrder()->value('id'), 'status' => 'waiting']);
            $s->players()->create(['ip_address' => $r->ip(), 'is_bot' => false, 'last_activity_at' => now(), 'joined_at' => now()]);

            return $s;
        });
        event(new PlayerJoined($s->code, $r->ip()));
        if ($data['bot_after_timeout'] ?? false) {
            BotJoinJob::dispatch($s->id)->delay(now()->addSeconds(AppSetting::current()->bot_wait_seconds));
        }

        return redirect()->route('session.show', $s->code);
    }

    public function playWithBot(Request $r)
    {
        $s = DB::transaction(function () use ($r) {
            $s = DrawingSession::create(['code' => $this->newCode(), 'background_id' => Background::inRandomOrder()->value('id'), 'status' => 'active']);
            $s->players()->create(['ip_address' => $r->ip(), 'is_bot' => false, 'last_activity_at' => now(), 'joined_at' => now()]);
            $s->players()->create(['ip_address' => null, 'is_bot' => true, 'last_activity_at' => now(), 'joined_at' => now()]);

            return $s;
        });
        event(new PlayerJoined($s->code, $r->ip()));
        event(new BotJoined($s->code));
        BotDrawJob::dispatch($s->id)->delay(now()->addSecond());

        return redirect()->route('session.show', $s->code);
    }

    public function join(Request $r)
    {
        $d = $r->validate(['code' => ['required', 'string', 'size:6', 'regex:/^[A-Za-z0-9]{6}$/']]);
        $s = DrawingSession::where('code', strtoupper($d['code']))->whereIn('status', ['waiting', 'active'])->first();
        if (! $s) {
            return back()->with('error', 'Session code is invalid or ended.');
        }if ($this->activeHuman($s, $r->ip())) {
            return redirect()->route('session.show', $s->code);
        }$active = $s->players()->whereNull('left_at')->get();
        if ($active->count() >= 2 || $active->contains('is_bot', true)) {
            return back()->with('error', 'Session is full.');
        }$s->players()->create(['ip_address' => $r->ip(), 'is_bot' => false, 'last_activity_at' => now(), 'joined_at' => now()]);
        $s->update(['status' => 'active']);
        event(new PlayerJoined($s->code, $r->ip()));

        return redirect()->route('session.show', $s->code);
    }

    public function show(Request $r, string $code)
    {
        $s = DrawingSession::with(['background', 'players' => fn ($q) => $q->whereNull('left_at')])->where('code', strtoupper($code))->whereIn('status', ['waiting', 'active'])->firstOrFail();
        $me = $this->activeHuman($s, $r->ip());
        if (! $me) {
            return redirect()->route('landing')->with('error', 'Join this session from the landing page first.');
        }

        return view('session', ['session' => $s, 'me' => $me, 'initialStrokes' => $s->recordedStrokes()->orderBy('drawn_at')->get(['points', 'color', 'size'])]);
    }

    public function state(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->activeHuman($s, $r->ip()), 403);

        $deadline = $s->finish_deadline_at
            ? \Illuminate\Support\Carbon::parse($s->finish_deadline_at)
            : null;
        if ($s->finish_state === 'waiting' && $deadline && $deadline->isPast()) {
            $this->finalizeSession($s, $s->finished_by_ip ?? $r->ip());
            $s->refresh();
            $deadline = null;
        }

        $settings = AppSetting::current();

        return response()->json([
            'state' => $s->finish_state ?? 'drawing',
            'remainingSeconds' => $deadline ? max(0, now()->diffInSeconds($deadline, false)) : 0,
            'finishedByIp' => $s->finished_by_ip,
            'deadlineAt' => $deadline?->toIso8601String(),
            'finishedAt' => $s->finished_at,
            'imageUrl' => $s->ai_image_url,
            'aiEnabled' => $settings->ai_generation_enabled,
            'canGenerateAi' => $settings->ai_generation_enabled
                && $s->finish_state === 'finalized'
                && ! $s->ai_image_url
                && $s->finished_by_ip === $r->ip(),
        ]);
    }

    public function leave(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        if ($p = $this->activeHuman($s, $r->ip())) {
            $p->update(['left_at' => now()]);
            event(new PlayerLeft($s->code, $r->ip()));
        }if (! $s->players()->whereNull('left_at')->where('is_bot', false)->exists()) {
            $s->update(['status' => 'ended']);
        }

        return redirect()->route('landing');
    }

    public function report(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        $me = $this->activeHuman($s, $r->ip());
        if (! $me) {
            return redirect()->route('landing')->with('error', 'Join this session from the landing page first.');
        }
        $other = $s->players()->whereNull('left_at')->where('is_bot', false)->where('ip_address', '!=', $r->ip())->first();
        if ($other) {
            Report::create(['session_id' => $s->id, 'session_code' => $s->code, 'reporter_ip' => $r->ip(), 'reported_ip' => $other->ip_address]);
        }$me->update(['left_at' => now()]);
        event(new PlayerLeft($s->code, $r->ip()));

        return redirect()->route('landing')->with('message', $other ? 'Report submitted.' : 'You left the session. No human participant was available to report.');
    }

    public function clear(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->activeHuman($s, $r->ip()), 403);
        $s->recordedStrokes()->delete();
        event(new CanvasCleared($s->code));

        return response()->noContent();
    }

    public function recordStroke(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->activeHuman($s, $r->ip()), 403);
        $d = $r->validate(['points' => 'required|array|min:2|max:1000', 'points.*.x' => 'required|numeric|min:0|max:800', 'points.*.y' => 'required|numeric|min:0|max:600', 'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'size' => 'required|integer|min:1|max:40', 'drawn_at' => 'nullable|date', 'client_stroke_id' => 'required|string|max:100']);
        $stroke = $s->recordedStrokes()->create(['player_ip' => $r->ip(), 'points' => $d['points'], 'color' => $d['color'], 'size' => $d['size'], 'drawn_at' => $d['drawn_at'] ?? now()]);
        broadcast(new StrokeRecorded($s->code, [
            'id' => $stroke->id,
            'points' => $stroke->points,
            'color' => $stroke->color,
            'size' => $stroke->size,
            'client_stroke_id' => $d['client_stroke_id'],
        ]))->toOthers();

        return response()->json(['id' => $stroke->id], 201);
    }

    public function heartbeat(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        $p = $this->activeHuman($s, $r->ip());
        abort_unless($p, 403);
        $p->update(['last_activity_at' => now()]);

        return response()->noContent();
    }

    public function finish(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        $p = $this->activeHuman($s, $r->ip());
        abort_unless($p, 403);
        $settings = AppSetting::current();
        $finishWaitSeconds = $settings->finish_wait_seconds;

        $state = $s->finish_state ?? 'drawing';

        if ($state === 'finalized') {
            return response()->json([
                'state' => 'finalized',
                'remainingSeconds' => 0,
                'finishedByIp' => $s->finished_by_ip,
                'finishedAt' => $s->finished_at,
                'aiEnabled' => $settings->ai_generation_enabled,
                'canGenerateAi' => $settings->ai_generation_enabled && ! $s->ai_image_url && $s->finished_by_ip === $r->ip(),
            ]);
        }

        if ($state === 'waiting' && $s->finished_by_ip && $s->finished_by_ip !== $r->ip()) {
            $deadline = $s->finish_deadline_at ? \Illuminate\Support\Carbon::parse($s->finish_deadline_at) : null;
            if ($deadline && $deadline->isPast()) {
                $state = 'finalized';
            }
        }

        if ($state === 'finalized') {
            return response()->json([
                'state' => 'finalized',
                'remainingSeconds' => 0,
                'finishedByIp' => $s->finished_by_ip,
                'finishedAt' => $s->finished_at,
                'aiEnabled' => $settings->ai_generation_enabled,
                'canGenerateAi' => $settings->ai_generation_enabled && ! $s->ai_image_url && $s->finished_by_ip === $r->ip(),
            ]);
        }

        if ($state === 'waiting') {
            $deadline = $s->finish_deadline_at ? \Illuminate\Support\Carbon::parse($s->finish_deadline_at) : null;
            if ($s->finished_by_ip === $r->ip()) {
                return response()->json([
                    'state' => 'waiting',
                    'remainingSeconds' => $deadline ? max(0, now()->diffInSeconds($deadline, false)) : $finishWaitSeconds,
                    'finishedByIp' => $s->finished_by_ip,
                    'deadlineAt' => $deadline?->toIso8601String(),
                    'aiEnabled' => $settings->ai_generation_enabled,
                    'canGenerateAi' => false,
                ]);
            }

            $this->finalizeSession($s, $r->ip(), true);

            return response()->json([
                'state' => 'finalized',
                'remainingSeconds' => 0,
                'finishedByIp' => $s->finished_by_ip,
                'finishedAt' => $s->finished_at,
                'aiEnabled' => $settings->ai_generation_enabled,
                'canGenerateAi' => $settings->ai_generation_enabled && ! $s->ai_image_url && $s->finished_by_ip === $r->ip(),
            ]);
        }

        $hasOtherParticipant = $s->players()
            ->whereNull('left_at')
            ->where('id', '!=', $p->id)
            ->exists();

        if (! $hasOtherParticipant) {
            $this->finalizeSession($s, $r->ip());
            $s->refresh();

            return response()->json([
                'state' => 'finalized',
                'remainingSeconds' => 0,
                'finishedByIp' => $s->finished_by_ip,
                'finishedAt' => $s->finished_at,
                'aiEnabled' => $settings->ai_generation_enabled,
                'canGenerateAi' => $settings->ai_generation_enabled && ! $s->ai_image_url,
            ]);
        }

        $this->lockSessionForFinish($s, $r->ip(), $finishWaitSeconds);

        return response()->json([
            'state' => 'waiting',
            'remainingSeconds' => $finishWaitSeconds,
            'finishedByIp' => $r->ip(),
            'deadlineAt' => now()->addSeconds($finishWaitSeconds)->toIso8601String(),
            'aiEnabled' => $settings->ai_generation_enabled,
            'canGenerateAi' => false,
        ]);
    }

    public function resetSession(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->activeHuman($s, $r->ip()), 403);

        $s->update([
            'finish_state' => 'drawing',
            'finished_by_ip' => null,
            'finish_deadline_at' => null,
            'finished_at' => null,
            'ai_image_url' => null,
            'ai_prompt' => null,
        ]);
        $s->recordedStrokes()->delete();

        return response()->json(['state' => 'drawing']);
    }

    public function generateAi(Request $r, string $code)
    {
        $s = DrawingSession::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->activeHuman($s, $r->ip()), 403);
        abort_unless(AppSetting::current()->ai_generation_enabled, 409, 'AI image generation is disabled by the administrator.');

        $data = $r->validate([
            'image_data_url' => ['required', 'string'],
            'prompt' => ['nullable', 'string'],
        ]);

        $apiKey = config('services.sensenova.api_key');
        if (blank($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'SENSENOVA_API_KEY is not configured in the server environment.',
            ], 500);
        }

        $prompt = $data['prompt'] ?? 'This is a rough hand-drawn sketch made collaboratively by two people. Turn it into a polished, finished illustration. Preserve every drawn shape, object, and layout exactly as it appears — do not add, remove, or move any elements. Only clean up the lines, add color, shading, and texture to make it look like a professional piece of art. Keep the composition and proportions identical to the sketch.';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://token.sensenova.ai/v1/images/edits', [
                'model' => 'sensenova-u1.5-lite',
                'images' => [[ 'image_url' => $data['image_data_url'] ]],
                'prompt' => $prompt,
                'size' => 'auto',
                'n' => 1,
                'watermark' => false,
                'response_format' => 'url',
                'output_format' => 'png',
            ]);

            if (! $response->successful()) {
                $status = $response->status();
                $message = $response->json('error.message') ?? $response->json('message');

                if ($status === 401) {
                    $message = 'SenseNova rejected SENSENOVA_API_KEY. Replace it with an active TokenPlan API key from the SenseNova console.';
                } elseif ($status === 403) {
                    $message = 'SenseNova denied image editing for this request. Check that the key can access sensenova-u1.5-lite and that the prompt is supported.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message ?? 'SenseNova request failed.',
                    'provider_status' => $status,
                ], 502);
            }

            $payload = $response->json();
            $imageUrl = $this->extractSenseNovaImageUrl($payload);
            if (! $imageUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'SenseNova returned no usable image URL.',
                ], 500);
            }

            $imageResponse = Http::timeout(30)->get($imageUrl);
            if (! $imageResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SenseNova generated the image, but it could not be saved to the application.',
                ], 502);
            }

            $imagePath = 'ai-results/'.$s->code.'/'.Str::uuid().'.png';
            Storage::disk('public')->put($imagePath, $imageResponse->body());
            $imageUrl = '/storage/'.$imagePath;

            $s->update([
                'ai_image_url' => $imageUrl,
                'ai_prompt' => $prompt,
            ]);

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'state' => 'done',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function lockSessionForFinish(DrawingSession $s, string $ip, int $waitSeconds): void
    {
        $s->update([
            'finish_state' => 'waiting',
            'finished_by_ip' => $ip,
            'finish_deadline_at' => now()->addSeconds($waitSeconds),
            'finished_at' => null,
        ]);
    }

    protected function finalizeSession(DrawingSession $s, string $ip, bool $completedByOtherPlayer = false): void
    {
        $s->update([
            'finish_state' => 'finalized',
            'finished_by_ip' => $completedByOtherPlayer ? $ip : ($s->finished_by_ip ?? $ip),
            'finish_deadline_at' => null,
            'finished_at' => now(),
        ]);
    }

    protected function extractSenseNovaImageUrl(mixed $value): ?string
    {
        if (! is_array($value) && ! is_object($value)) {
            return null;
        }

        $value = (array) $value;

        foreach (['image_url', 'imageUrl', 'url', 'result'] as $key) {
            if (isset($value[$key]) && is_string($value[$key]) && $value[$key] !== '') {
                return $value[$key];
            }
        }

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                $nested = $this->extractSenseNovaImageUrl($item);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
