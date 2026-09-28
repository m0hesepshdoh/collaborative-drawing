<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\DrawingSession;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $r)
    {
        $sessions = DrawingSession::with(['background', 'recordedStrokes' => fn ($q) => $q->latest('drawn_at')->limit(150)])->whereIn('status', ['waiting', 'active'])->latest()->limit(6)->get();

        return view('landing', ['sessions' => $sessions, 'isBanned' => Ban::where('ip_address', $r->ip())->exists()]);
    }
}
