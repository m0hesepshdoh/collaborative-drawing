 <?php $__env->startSection('body'); ?>
<main class="p-6 max-w-6xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <h1 class="font-display text-3xl text-chalk mt-8">Recorded Human Drawings</h1>
    <div class="mt-5 grid md:grid-cols-2 gap-4"><?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php ($first = $rows->first()); ?>
        <div class="bg-chalk/5 border border-chalk/10 p-4 rounded-xl">
            <h2 class="font-semibold text-chalk">Session <?php echo e($first->session?->code ?? $first->session_id); ?></h2>
            <p class="text-sm text-slate">Player <?php echo e($first->player_ip); ?> · <?php echo e($rows->count()); ?> strokes</p>
            <canvas width="400" height="260" class="preview mt-3 w-full bg-chalk rounded-lg"
                data-strokes='<?php echo json_encode($rows->map(fn($x) => ["points" => $x->points, "color" => $x->color, "size" => $x->size])->values()) ?>'></canvas>
            <div class="mt-3 flex gap-3">
                <form method="post" action="<?php echo e(route('admin.recordings.approve', $first->id)); ?>"><?php echo csrf_field(); ?><button
                        class="px-3 py-1.5 bg-emerald-900/40 border border-emerald-700 text-emerald-300 hover:bg-emerald-900/60 rounded-lg transition-colors text-sm">Approve</button>
                </form>
                <form method="post" action="<?php echo e(route('admin.recordings.reject', $first->id)); ?>"><?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?><button
                        class="px-3 py-1.5 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded-lg transition-colors text-sm">Reject
                        / Delete</button></form>
            </div>
        </div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p class="text-slate">No recordings yet.</p><?php endif; ?>
    </div>
</main>
<script>document.querySelectorAll('.preview').forEach(c => { let x = c.getContext('2d'), d = JSON.parse(c.dataset.strokes); d.forEach(s => { if (s.points.length < 2) return; x.strokeStyle = s.color; x.lineWidth = s.size; x.beginPath(); x.moveTo(s.points[0].x / 2, s.points[0].y / 2); s.points.slice(1).forEach(p => x.lineTo(p.x / 2, p.y / 2)); x.stroke() }) })</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/bot-patterns/recordings.blade.php ENDPATH**/ ?>