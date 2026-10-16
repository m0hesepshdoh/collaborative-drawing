 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-6xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="font-display text-3xl text-chalk mt-8">Background Photos</h1>
        <form method="post" enctype="multipart/form-data" action="<?php echo e(route('backgrounds.store')); ?>"
            class="mt-5 flex gap-3 items-center"><?php echo csrf_field(); ?><input type="file" name="image" accept="image/*" required
                class="bg-chalk/10 border border-chalk/20 rounded-lg p-2 text-sm file:mr-3 file:px-3 file:py-1.5 file:rounded file:border-0 file:bg-clay file:text-ink file:cursor-pointer"><button
                class="bg-clay hover:bg-clay/90 text-ink font-medium px-4 py-2 rounded-lg transition-colors">Upload</button>
        </form>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6"><?php $__currentLoopData = $backgrounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-chalk/5 border border-chalk/10 p-3 rounded-xl"><img src="<?php echo e(asset('storage/' . $b->path)); ?>"
                    class="w-full aspect-video object-cover rounded-lg">
                <div class="mt-2 flex justify-between items-center gap-2"><span
                        class="truncate text-slate text-sm"><?php echo e($b->filename); ?></span>
                    <form method="post" action="<?php echo e(route('backgrounds.destroy', $b)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button
                            class="text-clay hover:underline text-sm">Delete</button></form>
                </div>
        </div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
</main><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/backgrounds.blade.php ENDPATH**/ ?>