@extends('layout') @section('body')
    <main class="p-6 max-w-6xl mx-auto">@include('admin.nav')
        <h1 class="font-display text-3xl text-chalk mt-8">Background Photos</h1>
        <form method="post" enctype="multipart/form-data" action="{{ route('backgrounds.store') }}"
            class="mt-5 flex gap-3 items-center">@csrf<input type="file" name="image" accept="image/*" required
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 text-sm file:mr-3 file:px-3 file:py-1.5 file:rounded file:border-0 file:bg-clay file:text-ink file:cursor-pointer"><button
                class="bg-clay hover:bg-clay/90 text-ink font-medium px-4 py-2 rounded-lg transition-colors">Upload</button>
        </form>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">@foreach($backgrounds as $b)
            <div class="bg-chalk/5 border border-chalk/10 p-3 rounded-xl"><img src="{{ asset('storage/' . $b->path) }}"
                    class="w-full aspect-video object-cover rounded-lg">
                <div class="mt-2 flex justify-between items-center gap-2"><span
                        class="truncate text-slate text-sm">{{ $b->filename }}</span>
                    <form method="post" action="{{ route('backgrounds.destroy', $b) }}">@csrf @method('DELETE')<button
                            class="text-clay hover:underline text-sm">Delete</button></form>
                </div>
        </div>@endforeach
        </div>
</main>@endsection