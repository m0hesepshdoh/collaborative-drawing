 <?php $__env->startSection('body'); ?>
    <main class="mx-auto max-w-3xl p-6">
        <?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="mt-8 font-display text-3xl text-chalk">Session Settings</h1>

        <?php if($errors->any()): ?>
            <div class="mt-5 rounded border border-clay bg-clay/10 px-4 py-3 text-sm">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(route('admin.settings.update')); ?>" class="mt-6 space-y-5">
            <?php echo csrf_field(); ?>
            <label class="flex items-center gap-3 border-b border-chalk/10 pb-5">
                <input type="checkbox" name="ai_generation_enabled" value="1"
                    <?php if(old('ai_generation_enabled', $settings->ai_generation_enabled)): echo 'checked'; endif; ?>
                    class="size-5 accent-clay">
                <span>
                    <span class="block text-sm font-medium">Enable AI image generation</span>
                    <span class="block text-xs text-slate">When off, players can download the finished canvas as PNG.</span>
                </span>
            </label>

            <label class="block max-w-xs text-sm">
                <span class="mb-1 block">Wait for another player to join before adding the bot (seconds)</span>
                <input type="number" name="bot_wait_seconds" min="1" max="3600" required
                    value="<?php echo e(old('bot_wait_seconds', $settings->bot_wait_seconds)); ?>"
                    class="w-full rounded border border-chalk/20 bg-chalk/10 px-3 py-2 text-chalk">
            </label>

            <label class="block max-w-xs text-sm">
                <span class="mb-1 block">Time for the second player to finish (seconds)</span>
                <input type="number" name="finish_wait_seconds" min="10" max="3600" required
                    value="<?php echo e(old('finish_wait_seconds', $settings->finish_wait_seconds)); ?>"
                    class="w-full rounded border border-chalk/20 bg-chalk/10 px-3 py-2 text-chalk">
            </label>

            <button type="submit"
                class="rounded border border-clay bg-clay px-4 py-2 font-medium text-ink hover:bg-clay/90">Save settings</button>
        </form>
    </main>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/settings.blade.php ENDPATH**/ ?>