@extends('layout') @section('body')
    <main class="p-6 max-w-5xl mx-auto">@include('admin.nav')
        <h1 class="font-display text-3xl text-chalk mt-8">Bans</h1>
        <form method="post" action="{{ route('admin.ban') }}" class="mt-5 flex flex-wrap gap-2">@csrf<input
                name="ip_address" required placeholder="IP address"
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 text-chalk placeholder:text-slate focus:outline-none focus:border-clay"><input
                name="reason" placeholder="Reason"
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 flex-1 text-chalk placeholder:text-slate focus:outline-none focus:border-clay"><button
                class="bg-clay/20 border border-clay hover:bg-clay/30 px-4 rounded-lg transition-colors">Ban</button></form>
        <div class="mt-5">@foreach($bans as $b)
            <div class="border-t border-chalk/10 py-3 flex gap-4 items-center"><span
                    class="font-mono">{{ $b->ip_address }}</span><span class="text-slate flex-1">{{ $b->reason }}</span>
                <form method="post" action="{{ route('admin.unban', $b->id) }}">@csrf @method('DELETE')<button
                        class="text-emerald-400 hover:underline">Unban</button></form>
        </div>@endforeach
        </div>
</main>@endsection