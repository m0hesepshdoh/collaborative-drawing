@extends('layout') @section('body')
    <main class="p-6 max-w-4xl mx-auto">@include('admin.nav')
        <h1 class="font-display text-3xl text-chalk mt-8">{{ $title }}</h1><canvas id="p" width="800" height="600"
            class="mt-5 w-full bg-chalk rounded-lg"></canvas>
    </main>
    <script>const s = @json($strokes), c = document.getElementById('p'), x = c.getContext('2d'); let i = 0; function next() { if (i >= s.length) return; let a = s[i++]; if (a.points.length > 1) { x.strokeStyle = a.color; x.lineWidth = a.size; x.lineCap = 'round'; x.beginPath(); x.moveTo(a.points[0].x, a.points[0].y); a.points.slice(1).forEach(p => x.lineTo(p.x, p.y)); x.stroke() } setTimeout(next, a.delay_ms || 150) } next()</script>
@endsection