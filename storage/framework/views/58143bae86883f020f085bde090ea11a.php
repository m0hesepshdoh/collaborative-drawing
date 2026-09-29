<nav class="flex flex-wrap gap-3 text-sm items-center"><a href="<?php echo e(route('admin.dashboard')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Sessions</a><a
        href="<?php echo e(route('backgrounds.index')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Backgrounds</a><a
        href="<?php echo e(route('admin.reports')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Reports</a><a
        href="<?php echo e(route('admin.bans')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Bans</a><a
        href="<?php echo e(route('admin.recordings.index')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Recordings</a><a
        href="<?php echo e(route('admin.botPatterns.index')); ?>"
        class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Bot
        Patterns</a>
        <a href="<?php echo e(route('admin.settings')); ?>"
                class="px-3 py-1.5 rounded-lg bg-chalk/10 border border-chalk/20 hover:border-clay transition-colors">Settings</a>
    <form method="post" action="<?php echo e(route('admin.logout')); ?>" class="ml-auto"><?php echo csrf_field(); ?><button
            class="px-3 py-1.5 rounded-lg bg-clay/20 border border-clay text-chalk hover:bg-clay/30 transition-colors">Logout</button>
    </form>
</nav><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/nav.blade.php ENDPATH**/ ?>