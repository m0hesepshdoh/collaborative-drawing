@extends('layout')
@section('body')
    <main class="mx-auto max-w-6xl p-6">
        @include('admin.nav')
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-3xl text-chalk">Performance Measurements</h1>
            <a href="{{ route('admin.measurements.export') }}" class="rounded border border-clay bg-clay px-4 py-2 font-medium text-ink">Download CSV</a>
        </div>
        @if (session('message'))
            <p class="mt-4 rounded border border-clay/30 bg-clay/10 px-4 py-3 text-sm">{{ session('message') }}</p>
        @endif
        <div class="mt-6 space-y-4">
            @forelse ($measurements as $eventType => $rows)
                <section class="rounded border border-chalk/20 bg-chalk/5 p-4">
                    <h2 class="text-lg font-semibold capitalize">{{ str_replace('_', ' ', $eventType) }}</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-full text-left text-sm">
                            <thead><tr><th class="pb-2">Session</th><th>Client</th><th>Value (ms)</th><th>Meta</th><th>Created</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $measurement)
                                    <tr class="border-t border-chalk/10"><td>{{ $measurement->session_code }}</td><td class="break-all">{{ $measurement->client_id }}</td><td>{{ $measurement->value_ms }}</td><td class="break-all">{{ json_encode($measurement->meta) }}</td><td>{{ $measurement->created_at }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @empty
                <p class="mt-4 text-slate">No measurements have been uploaded yet.</p>
            @endforelse
        </div>
    </main>
@endsection
