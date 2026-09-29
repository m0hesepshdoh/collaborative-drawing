<nav class="flex flex-wrap gap-3 text-sm items-center"><a href="{{ route('admin.dashboard') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Sessions</a><a
        href="{{ route('backgrounds.index') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Backgrounds</a><a
        href="{{ route('admin.reports') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Reports</a><a
        href="{{ route('admin.bans') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Bans</a><a
        href="{{ route('admin.recordings.index') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Recordings</a><a
        href="{{ route('admin.botPatterns.index') }}"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Bot
        Patterns</a>
        <a href="{{ route('admin.settings') }}"
                class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Settings</a>
    <form method="post" action="{{ route('admin.logout') }}" class="ml-auto">@csrf<button
            class="px-3 py-1.5 rounded-lg bg-clay/20 border border-clay text-chalk hover:bg-clay/30 transition-colors">Logout</button>
    </form>
</nav>