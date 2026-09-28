@extends('layout') @section('body')
    <main class="min-h-screen grid place-items-center p-6 relative overflow-hidden">
        <div class="fixed inset-0 -z-10">
            <div class="h-full w-full bg-linear-to-b from-[#221f1b] via-ink to-[#231a16]"></div>
            <svg class="absolute inset-0 h-full w-full opacity-20" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="scribble" width="120" height="120" patternUnits="userSpaceOnUse">
                        <path d="M10 60 Q 30 10, 60 60 T 110 60" stroke="#C4694B" stroke-width="1.5" fill="none" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#scribble)" />
            </svg>
            <div class="absolute inset-0 bg-ink/70"></div>
        </div>
        <form method="post" action="{{ route('admin.login.submit') }}"
            class="w-full max-w-sm bg-ink/80 backdrop-blur border border-chalk/20 p-7 rounded-2xl relative z-10">@csrf<h1
                class="font-display text-2xl text-chalk">Admin Access</h1>
            <p class="text-slate text-sm mt-2">Enter the admin password to continue.</p><input type="password"
                name="password" autofocus required
                class="mt-5 w-full bg-chalk/10 border border-chalk/20 rounded-lg p-3 text-chalk placeholder:text-slate focus:outline-none focus:border-clay"
                placeholder="Password">@if(session('error'))
                <p class="mt-2 text-clay text-sm">{{ session('error') }}</p>@endif<button
                class="mt-4 w-full bg-clay hover:bg-clay/90 text-ink font-medium rounded-lg p-3 transition-colors">Sign
                in</button>
        </form>
</main>@endsection