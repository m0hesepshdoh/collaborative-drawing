<?php

namespace App\Http\Controllers;

use App\Events\PlayerKicked;
use App\Models\Ban;
use App\Models\AppSetting;
use App\Models\DrawingSession;
use App\Models\Measurement;
use App\Models\Player;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AdminController extends Controller
{
    public function settings()
    {
        return view('admin.settings', ['settings' => AppSetting::current()]);
    }

    public function updateSettings(Request $r)
    {
        $data = $r->validate([
            'bot_wait_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
            'finish_wait_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
        ]);

        AppSetting::current()->update([
            ...$data,
            'ai_generation_enabled' => $r->boolean('ai_generation_enabled'),
        ]);

        return back()->with('message', 'Settings saved.');
    }

    public function measurements()
    {
        $measurements = Measurement::latest('created_at')->get()->groupBy('event_type');

        return view('admin.measurements', compact('measurements'));
    }

    public function exportMeasurements()
    {
        $filename = 'measurements_'.now()->format('Ymd_His').'.csv';
        $response = Response::stream(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['session_code', 'client_id', 'event_type', 'value_ms', 'meta_json', 'created_at']);
            Measurement::query()->orderBy('created_at')->chunkById(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [$row->session_code, $row->client_id, $row->event_type, $row->value_ms, json_encode($row->meta), $row->created_at]);
                }
            });
            fclose($handle);
        }, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename={$filename}"]);

        return $response;
    }

    public function health()
    {
        $databaseSize = DB::selectOne('SELECT SUM(data_length + index_length) AS bytes FROM information_schema.tables WHERE table_schema = DATABASE()')?->bytes ?? 0;

        return response()->json([
            'memory_get_peak_usage' => memory_get_peak_usage(true),
            'memory_get_usage' => memory_get_usage(true),
            'database_size_bytes' => (int) $databaseSize,
        ]);
    }

    public function dashboard()
    {
        $sessions = DrawingSession::with([
            'background',
            'players' => fn ($q) => $q->whereNull('left_at'),
            'recordedStrokes' => fn ($q) => $q->select(['id', 'session_id', 'points', 'color', 'size'])->orderBy('drawn_at'),
        ])->whereIn('status', ['waiting', 'active'])->latest()->get();
        $previews = $sessions->map(fn ($session) => [
            'code' => $session->code,
            'background' => $session->background ? asset('storage/'.$session->background->path) : null,
            'strokes' => $session->recordedStrokes->map(fn ($stroke) => [
                'points' => $stroke->points,
                'color' => $stroke->color,
                'size' => $stroke->size,
            ])->values(),
        ])->values();

        return view('admin.dashboard', compact('sessions', 'previews'));
    }

    public function deleteSession(int $id)
    {
        DrawingSession::findOrFail($id)->delete();

        return back()->with('message', 'Session deleted.');
    }

    public function deleteAllSessions()
    {
        DrawingSession::query()->delete();

        return back()->with('message', 'All sessions deleted.');
    }

    public function removePlayer(int $id, int $playerId)
    {
        $s = DrawingSession::findOrFail($id);
        $p = $s->players()->whereKey($playerId)->firstOrFail();
        event(new PlayerKicked($s->code, $p->ip_address));
        $p->update(['left_at' => now()]);
        if (! $s->players()->whereNull('left_at')->exists()) {
            $s->update(['status' => 'ended']);
        }

return back()->with('message', 'Player removed.');
    }

    public function reports()
    {
        return view('admin.reports', ['reports' => Report::latest('created_at')->get()]);
    }

    public function bans()
    {
        return view('admin.bans', ['bans' => Ban::latest('created_at')->get()]);
    }

    public function ban(Request $r)
    {
        $d = $r->validate(['ip_address' => 'required|ip', 'reason' => 'nullable|string|max:255']);
        Ban::updateOrCreate(['ip_address' => $d['ip_address']], ['reason' => $d['reason'] ?? null]);
        Player::where('ip_address', $d['ip_address'])->whereNull('left_at')->update(['left_at' => now()]);

        return back()->with('message', 'IP banned.');
    }

    public function unban(int $id)
    {
        Ban::findOrFail($id)->delete();

        return back()->with('message','IP unbanned.');
    }
}
