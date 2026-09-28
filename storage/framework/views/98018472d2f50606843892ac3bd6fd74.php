 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-7xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="flex justify-between items-center mt-8">
            <h1 class="font-display text-3xl text-chalk">Ongoing Sessions</h1>
            <form method="post" action="<?php echo e(route('admin.sessions.deleteAll')); ?>"
                onsubmit="return confirm('Delete every session?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button
                    class="px-3 py-2 bg-clay/20 border border-clay hover:bg-clay/30 rounded-lg transition-colors">Delete
                    all</button></form>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate">
                        <th class="py-2">Code</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Started</th>
                        <th>Last activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-t border-chalk/10 align-top">
                        <td class="py-3 font-mono tracking-widest"><?php echo e($s->code); ?></td>
                        <td class="text-slate"><?php echo e($s->status); ?></td>
                        <td><?php $__currentLoopData = $s->players; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center gap-2"><?php echo e($p->is_bot ? 'Bot' : $p->ip_address); ?>

                                <form class="inline" method="post"
                                    action="<?php echo e(route('admin.player.remove', [$s->id, $p->id])); ?>"><?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?><button class="text-clay hover:underline">remove</button></form>
                        </div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </td>
                        <td class="text-slate"><?php echo e($s->created_at); ?></td>
                        <td class="text-slate"><?php echo e($s->players->max('last_activity_at')); ?></td>
                        <td>
                            <form method="post" action="<?php echo e(route('admin.session.delete', $s->id)); ?>"><?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?><button class="text-clay hover:underline">Delete</button></form>
                        </td>
                </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
</main><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>