 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-5xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="font-display text-3xl text-chalk mt-8">Bans</h1>
        <form method="post" action="<?php echo e(route('admin.ban')); ?>" class="mt-5 flex flex-wrap gap-2"><?php echo csrf_field(); ?><input
                name="ip_address" required placeholder="IP address"
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 text-chalk placeholder:text-slate focus:outline-none focus:border-clay"><input
                name="reason" placeholder="Reason"
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 flex-1 text-chalk placeholder:text-slate focus:outline-none focus:border-clay"><button
                class="bg-clay/20 border border-clay hover:bg-clay/30 px-4 rounded-lg transition-colors">Ban</button></form>
        <div class="mt-5"><?php $__currentLoopData = $bans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="border-t border-chalk/10 py-3 flex gap-4 items-center"><span
                    class="font-mono"><?php echo e($b->ip_address); ?></span><span class="text-slate flex-1"><?php echo e($b->reason); ?></span>
                <form method="post" action="<?php echo e(route('admin.unban', $b->id)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button
                        class="text-emerald-400 hover:underline">Unban</button></form>
        </div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
</main><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/bans.blade.php ENDPATH**/ ?>