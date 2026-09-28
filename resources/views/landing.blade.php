@extends('layout') @section('body')
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Sketchline — Draw together, live</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            ink: '#171512',
                            chalk: '#F1ECE2',
                            clay: '#C4694B',
                            slate: '#5C6B73',
                        },
                        fontFamily: {
                            display: ['Georgia', 'ui-serif', 'serif'],
                        },
                    },
                },
            };
        </script>
    </head>

    <body class="bg-ink text-chalk min-h-screen relative overflow-x-hidden">

        {{-- Live background: blurred grid of ongoing sessions, or a fallback wash --}}
        <div class="fixed inset-0 -z-10">
            @if($sessions->count() >= 3)
                <div class="grid grid-cols-3 grid-rows-2 h-full w-full">
                    @foreach($sessions as $s)
                        <canvas class="preview w-full h-full blur-2xl scale-110 opacity-40 bg-[#2a2622]"
                            data-strokes='@json($s->recordedStrokes->map(fn($x) => ["points" => $x->points, "color" => $x->color, "size" => $x->size])->values())'></canvas>
                    @endforeach
                </div>
            @else
                <div class="h-full w-full bg-linear-to-b from-[#221f1b] via-ink to-[#231a16]"></div>
                <svg class="absolute inset-0 h-full w-full opacity-20" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="scribble" width="120" height="120" patternUnits="userSpaceOnUse">
                            <path d="M10 60 Q 30 10, 60 60 T 110 60" stroke="#C4694B" stroke-width="1.5" fill="none" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#scribble)" />
                </svg>
            @endif
            <div class="absolute inset-0 bg-ink/70"></div>
        </div>

        <main class="relative z-10 flex flex-col items-center justify-center min-h-screen px-6 py-16">
            <h1 class="font-display text-5xl sm:text-6xl text-chalk mb-2 tracking-tight">Sketchline</h1>
            <p class="text-slate mb-12 text-center max-w-sm">Two people, one canvas. Draw with a friend across the world, or
                warm up with the bot.</p>

            @if($isBanned)
                <div class="bg-clay/20 border border-clay text-chalk rounded-lg px-6 py-4 max-w-sm text-center">
                    Your IP has been banned from this service.
                </div>
            @else
                <div class="w-full max-w-sm space-y-4">

                    @if (session('error'))
                        <div class="bg-clay/20 border border-clay rounded-lg px-4 py-3 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('session.join') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" name="code" maxlength="6" placeholder="ENTER CODE" required
                            class="flex-1 bg-chalk/10 border border-chalk/20 rounded-lg px-4 py-3 uppercase tracking-widest text-center placeholder:text-slate focus:outline-none focus:border-clay">
                        <button type="submit"
                            class="bg-chalk/10 border border-chalk/20 hover:border-clay rounded-lg px-4 py-3 transition-colors">Join</button>
                    </form>

                    <form action="{{ route('session.create') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full bg-clay hover:bg-clay/90 text-ink font-medium rounded-lg px-4 py-3 transition-colors">Start
                            a session</button>
                    </form>

                    <form action="{{ route('session.bot') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full bg-chalk/10 border border-chalk/20 hover:border-clay rounded-lg px-4 py-3 transition-colors">Play
                            with the bot</button>
                    </form>
                </div>
            @endif

            <p class="text-xs text-slate/70 mt-16 max-w-sm text-center">
                Drawings may be recorded and, used anonymously to improve the bot's drawing
                behaviour.
            </p>
        </main>

        <script>
            document.querySelectorAll('.preview').forEach(c => {
                const d = JSON.parse(c.dataset.strokes || '[]'), x = c.getContext('2d');
                c.width = 800; c.height = 600;
                d.forEach(s => {
                    if (!s.points?.length) return;
                    x.strokeStyle = s.color;
                    x.lineWidth = s.size;
                    x.lineCap = 'round';
                    x.beginPath();
                    x.moveTo(s.points[0].x, s.points[0].y);
                    s.points.slice(1).forEach(p => x.lineTo(p.x, p.y));
                    x.stroke();
                });
            });
        </script>
    </body>

    </html>
@endsection