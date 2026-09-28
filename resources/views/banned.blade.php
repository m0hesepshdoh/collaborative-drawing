@extends('layout') @section('body')
    <main class="min-h-screen grid place-items-center p-6">
        <div class="max-w-lg text-center">
            <h1 class="font-display text-4xl text-chalk">Access blocked</h1>
            <p class="mt-4 text-slate">Your IP has been banned from this service. Contact support.</p>
            <a href="{{ url('/') }}"
                class="inline-block mt-8 px-4 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded-lg transition-colors text-sm">Back
                to home</a>
        </div>
    </main>
@endsection