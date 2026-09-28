<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $r)
    {
        $r->validate(['password' => 'required|string']);
        if (! hash_equals((string) config('admin.password'), (string) $r->password)) {
            return back()->with('error', 'Wrong password.');
        }$r->session()->put('admin_authenticated', true);
        $r->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $r)
    {
        $r->session()->forget('admin_authenticated');
        $r->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
