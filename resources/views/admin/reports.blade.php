@extends('layout') @section('body')
    <main class="p-6 max-w-6xl mx-auto">@include('admin.nav')
        <h1 class="font-display text-3xl text-chalk mt-8">Reports</h1>
        <table class="w-full mt-5 text-sm">
            <thead>
                <tr class="text-left text-slate">
                    <th class="py-2">Session</th>
                    <th>Reporter</th>
                    <th>Reported</th>
                    <th>Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>@foreach($reports as $r)
                <tr class="border-t border-chalk/10">
                    <td class="py-3 font-mono">{{ $r->session_code }}</td>
                    <td class="text-slate">{{ $r->reporter_ip }}</td>
                    <td class="font-mono">{{ $r->reported_ip }}</td>
                    <td class="text-slate">{{ $r->created_at }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.ban') }}">@csrf<input type="hidden" name="ip_address"
                                value="{{ $r->reported_ip }}"><input type="hidden" name="reason"
                                value="Reported in session {{ $r->session_code }}"><button
                                class="text-clay hover:underline">Ban IP</button></form>
                    </td>
            </tr>@endforeach
            </tbody>
        </table>
</main>@endsection