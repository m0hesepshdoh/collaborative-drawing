 <?php $__env->startSection('body'); ?>
    <main class="p-6 max-w-4xl mx-auto"><?php echo $__env->make('admin.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <h1 class="font-display text-3xl text-chalk mt-8"><?php echo e($title); ?></h1><canvas id="p" width="800" height="600"
            class="mt-5 w-full bg-chalk rounded-lg"></canvas>
    </main>
    <script>const s = <?php echo json_encode($strokes, 15, 512) ?>, c = document.getElementById('p'), x = c.getContext('2d'); let i = 0; function next() { if (i >= s.length) return; let a = s[i++]; if (a.points.length > 1) { x.strokeStyle = a.color; x.lineWidth = a.size; x.lineCap = 'round'; x.beginPath(); x.moveTo(a.points[0].x, a.points[0].y); a.points.slice(1).forEach(p => x.lineTo(p.x, p.y)); x.stroke() } setTimeout(next, a.delay_ms || 150) } next()</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/admin/bot-patterns/preview.blade.php ENDPATH**/ ?>