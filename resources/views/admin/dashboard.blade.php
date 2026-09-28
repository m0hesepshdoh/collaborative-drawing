@extends('layout') @section('body')
    <main class="p-6 max-w-7xl mx-auto">@include('admin.nav')
        <div class="flex justify-between items-center mt-8">
            <h1 class="font-display text-3xl text-chalk">Ongoing Sessions</h1>
            <form method="post" action="{{ route('admin.sessions.deleteAll') }}"
                onsubmit="return confirm('Delete every session?')">@csrf @method('DELETE')<button
                    class="px-3 py-2 bg-clay/20 border border-clay hover:bg-clay/30 rounded-lg transition-colors">Delete
                    all</button></form>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate">
                        <th class="py-2">Code</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Started</th>
                        <th>Last activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>@foreach($sessions as $s)
                    <tr class="border-t border-chalk/10 align-top">
                        <td class="py-3 font-mono tracking-widest">{{ $s->code }}</td>
                        <td class="text-slate">{{ $s->status }}</td>
                        <td>@foreach($s->players as $p)
                            <div class="flex items-center gap-2">{{ $p->is_bot ? 'Bot' : $p->ip_address }}
                                <form class="inline" method="post"
                                    action="{{ route('admin.player.remove', [$s->id, $p->id]) }}">@csrf
                                    @method('DELETE')<button class="text-clay hover:underline">remove</button></form>
                        </div>@endforeach
                        </td>
                        <td class="text-slate">{{ $s->created_at }}</td>
                        <td class="text-slate">{{ $s->players->max('last_activity_at') }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.session.delete', $s->id) }}">@csrf
                                @method('DELETE')<button class="text-clay hover:underline">Delete</button></form>
                        </td>
                </tr>@endforeach
                </tbody>
            </table>
        </div>
</main>@endsection