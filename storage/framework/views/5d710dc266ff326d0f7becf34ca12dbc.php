<?php $__env->startSection('body'); ?>
    <main class="mx-auto max-w-6xl p-6">
        <?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-3xl text-chalk">Performance Measurements</h1>
            <a href="<?php echo e(route('admin.measurements.export')); ?>" class="rounded border border-clay bg-clay px-4 py-2 font-medium text-ink">Download CSV</a>
        </div>
        <?php if(session('message')): ?>
            <p class="mt-4 rounded border border-clay/30 bg-clay/10 px-4 py-3 text-sm"><?php echo e(session('message')); ?></p>
        <?php endif; ?>
        <div class="mt-6 space-y-4">
            <?php $__empty_1 = true; $__currentLoopData = $measurements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eventType => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <section class="rounded border border-chalk/20 bg-chalk/5 p-4">
                    <h2 class="text-lg font-semibold capitalize"><?php echo e(str_replace('_', ' ', $eventType)); ?></h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-full text-left text-sm">
                            <thead><tr><th class="pb-2">Session</th><th>Client</th><th>Value (ms)</th><th>Meta</th><th>Created</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $measurement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="border-t border-chalk/10"><td><?php echo e($measurement->session_code); ?></td><td class="break-all"><?php echo e($measurement->client_id); ?></td><td><?php echo e($measurement->value_ms); ?></td><td class="break-all"><?php echo e(json_encode($measurement->meta)); ?></td><td><?php echo e($measurement->created_at); ?></td></tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="mt-4 text-slate">No measurements have been uploaded yet.</p>
            <?php endif; ?>
        </div>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/measurements.blade.php ENDPATH**/ ?>