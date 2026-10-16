 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-6xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="font-display text-3xl text-chalk mt-8">Approved Bot Patterns</h1>
        <div class="mt-5 grid md:grid-cols-2 gap-4"><?php $__empty_1 = true; $__currentLoopData = $patterns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-chalk/5 border border-chalk/10 rounded-xl p-4">
                <h2 class="font-semibold text-chalk"><?php echo e($p->name); ?></h2>
                <p class="text-sm text-slate">Source <?php echo e($p->source_ip); ?> · <?php echo e(count($p->strokes)); ?> strokes</p>
                <div class="mt-3 flex gap-3 items-center">
                    <a class="px-3 py-1.5 bg-chalk/10 border border-chalk/20 hover:border-clay rounded-lg transition-colors text-sm"
                        href="<?php echo e(route('admin.botPatterns.preview', $p->id)); ?>">Preview</a>
                    <form method="post" action="<?php echo e(route('admin.botPatterns.destroy', $p->id)); ?>"><?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?><button
                            class="px-3 py-1.5 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded-lg transition-colors text-sm">Delete</button>
                    </form>
                </div>
        </div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p class="text-slate">No approved patterns yet.</p><?php endif; ?>
        </div>
</main><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/bot-patterns/index.blade.php ENDPATH**/ ?>