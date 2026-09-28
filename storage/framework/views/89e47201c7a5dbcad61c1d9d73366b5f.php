 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-6xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="font-display text-3xl text-chalk mt-8">Reports</h1>
        <table class="w-full mt-5 text-sm">
            <thead>
                <tr class="text-left text-slate">
                    <th class="py-2">Session</th>
                    <th>Reporter</th>
                    <th>Reported</th>
                    <th>Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-t border-chalk/10">
                    <td class="py-3 font-mono"><?php echo e($r->session_code); ?></td>
                    <td class="text-slate"><?php echo e($r->reporter_ip); ?></td>
                    <td class="font-mono"><?php echo e($r->reported_ip); ?></td>
                    <td class="text-slate"><?php echo e($r->created_at); ?></td>
                    <td>
                        <form method="post" action="<?php echo e(route('admin.ban')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="ip_address"
                                value="<?php echo e($r->reported_ip); ?>"><input type="hidden" name="reason"
                                value="Reported in session <?php echo e($r->session_code); ?>"><button
                                class="text-clay hover:underline">Ban IP</button></form>
                    </td>
            </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
</main><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/reports.blade.php ENDPATH**/ ?>