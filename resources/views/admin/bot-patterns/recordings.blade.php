@extends('layout') @section('body')
<main class="p-6 max-w-6xl mx-auto">@include('admin.nav')
    <h1 class="font-display text-3xl text-chalk mt-8">Recorded Human Drawings</h1>
    <div class="mt-5 grid md:grid-cols-2 gap-4">@forelse($groups as $key => $rows)@php($first = $rows->first())
        <div class="bg-chalk/5 border border-chalk/10 p-4 rounded-xl">
            <h2 class="font-semibold text-chalk">Session {{ $first->session?->code ?? $first->session_id }}</h2>
            <p class="text-sm text-slate">Player {{ $first->player_ip }} · {{ $rows->count() }} strokes</p>
            <canvas width="400" height="260" class="preview mt-3 w-full bg-chalk rounded-lg"
                data-strokes='@json($rows->map(fn($x) => ["points" => $x->points, "color" => $x->color, "size" => $x->size])->values())'></canvas>
            <div class="mt-3 flex gap-3">
                <form method="post" action="{{ route('admin.recordings.approve', $first->id) }}">@csrf<button
                        class="px-3 py-1.5 bg-emerald-900/40 border border-emerald-700 text-emerald-300 hover:bg-emerald-900/60 rounded-lg transition-colors text-sm">Approve</button>
                </form>
                <form method="post" action="{{ route('admin.recordings.reject', $first->id) }}">@csrf
                    @method('DELETE')<button
                        class="px-3 py-1.5 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded-lg transition-colors text-sm">Reject
                        / Delete</button></form>
            </div>
        </div>@empty<p class="text-slate">No recordings yet.</p>@endforelse
    </div>
</main>
<script>document.querySelectorAll('.preview').forEach(c => { let x = c.getContext('2d'), d = JSON.parse(c.dataset.strokes); d.forEach(s => { if (s.points.length < 2) return; x.strokeStyle = s.color; x.lineWidth = s.size; x.beginPath(); x.moveTo(s.points[0].x / 2, s.points[0].y / 2); s.points.slice(1).forEach(p => x.lineTo(p.x / 2, p.y / 2)); x.stroke() }) })</script>
@endsection