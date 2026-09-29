<?php

namespace App\Http\Controllers;

use App\Events\PlayerKicked;
use App\Models\Ban;
use App\Models\AppSetting;
use App\Models\DrawingSession;
use App\Models\Player;
use App\Models\Report;
use Illuminate\Http\Request;

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

    public function dashboard()
    {
        $sessions = DrawingSession::with(['players' => fn ($q) => $q->whereNull('left_at')])->whereIn('status', ['waiting', 'active'])->latest()->get();

        return view('admin.dashboard', compact('sessions'));
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
