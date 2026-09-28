@extends('layout') @section('body')
    <div class="h-screen flex flex-col overflow-hidden">
        <header class="h-16 shrink-0 px-4 flex items-center gap-3 border-b border-chalk/10 bg-ink/95 backdrop-blur">
            <div><span class="text-xs text-slate">SESSION</span>
                <div class="font-mono font-bold tracking-[.35em] text-chalk">{{ $session->code }}</div>
            </div>
            <div id="status" class="text-xs text-emerald-400">Connecting…</div>
            <div class="ml-auto flex items-center gap-2">
                <input id="color" type="color" value="#171512"
                    class="w-9 h-9 rounded bg-chalk/10 border border-chalk/20 cursor-pointer">
                <select id="size"
                    class="bg-chalk/10 border border-chalk/20 rounded p-2 text-chalk focus:outline-none focus:border-clay">
                    <option>2</option>
                    <option selected>5</option>
                    <option>10</option>
                    <option>20</option>
                </select>
                <button id="eraser"
                    class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Eraser</button>
                <button id="clear"
                    class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Clear</button>
                <form method="post" action="{{ route('session.report', $session->code) }}">@csrf<button
                        class="px-3 py-2 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded transition-colors">Report
                        & Leave</button></form>
                <form method="post" action="{{ route('session.leave', $session->code) }}">@csrf<button
                        class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Leave</button>
                </form>
            </div>
        </header>
        <div class="relative flex-1 min-h-0 bg-chalk"><canvas id="canvas"
                class="absolute inset-0 w-full h-full touch-none"></canvas></div>
    </div>
    <script>window.DRAWING_CONFIG = { code: @json($session->code), csrf: @json(csrf_token()), reverb: { key: @json(env('REVERB_APP_KEY')), host: @json(env('REVERB_HOST', '127.0.0.1')), port: @json((int) env('REVERB_PORT', 8080)), scheme: @json(env('REVERB_SCHEME', 'http')) }, background: @json($session->background ? asset('storage/' . $session->background->path) : null), meIp: @json(request()->ip()), initialStrokes: @json($initialStrokes->map(fn($x) => ['points' => $x->points, 'color' => $x->color, 'size' => $x->size])->values()) };</script>
<script type="module" src="{{ asset('js/canvas.js') }}"></script>@endsection