@extends('layout') @section('body')
    <main class="p-6 max-w-6xl mx-auto">@include('admin.nav')
        <h1 class="font-display text-3xl text-chalk mt-8">Approved Bot Patterns</h1>
        <div class="mt-5 grid md:grid-cols-2 gap-4">@forelse($patterns as $p)
            <div class="bg-chalk/5 border border-chalk/10 rounded-xl p-4">
                <h2 class="font-semibold text-chalk">{{ $p->name }}</h2>
                <p class="text-sm text-slate">Source {{ $p->source_ip }} · {{ count($p->strokes) }} strokes</p>
                <div class="mt-3 flex gap-3 items-center">
                    <a class="px-3 py-1.5 bg-chalk/10 border border-chalk/20 hover:border-clay rounded-lg transition-colors text-sm"
                        href="{{ route('admin.botPatterns.preview', $p->id) }}">Preview</a>
                    <form method="post" action="{{ route('admin.botPatterns.destroy', $p->id) }}">@csrf
                        @method('DELETE')<button
                            class="px-3 py-1.5 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded-lg transition-colors text-sm">Delete</button>
                    </form>
                </div>
        </div>@empty<p class="text-slate">No approved patterns yet.</p>@endforelse
        </div>
</main>@endsection