 <?php $__env->startSection('body'); ?>
    <div class="h-screen flex flex-col overflow-hidden bg-ink">
        <header class="shrink-0 border-b border-chalk/10 bg-ink/95 backdrop-blur">
            <div class="flex flex-col gap-2 px-3 py-2 sm:flex-row sm:items-center sm:gap-3 sm:px-4 sm:py-3">
                <div class="flex items-center gap-3">
                    <div><span class="text-xs text-slate">SESSION</span>
                        <div class="font-mono font-bold tracking-[.35em] text-chalk"><?php echo e($session->code); ?></div>
                    </div>
                    <div id="status" class="min-w-0 text-xs text-emerald-400">Connecting…</div>
                </div>
                <div class="flex w-full flex-wrap items-center gap-2 sm:ml-auto sm:w-auto sm:justify-end">
                    <input id="color" type="color" value="#171512"
                        class="w-9 h-9 rounded bg-chalk/10 border border-chalk/20 cursor-pointer">
                    <select id="size"
                        class="bg-chalk/10 border border-chalk/20 rounded p-2 text-chalk focus:outline-none focus:border-clay">
                        <option>2</option>
                        <option selected>5</option>
                        <option>10</option>
                        <option>20</option>
                    </select>
                    <button id="eraser"
                        class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Eraser</button>
                    <button id="clear"
                        class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Clear</button>
                    <button id="finish-drawing"
                        class="px-3 py-2 bg-emerald-500/20 border border-emerald-400 text-emerald-200 hover:bg-emerald-500/30 rounded transition-colors">Finish Drawing</button>
                    <button id="new-drawing"
                        class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Start Over</button>
                    <form method="post" action="<?php echo e(route('session.report', $session->code)); ?>"><?php echo csrf_field(); ?><button
                            class="px-3 py-2 bg-clay/20 border border-clay text-chalk hover:bg-clay/30 rounded transition-colors">Report
                            & Leave</button></form>
                    <form method="post" action="<?php echo e(route('session.leave', $session->code)); ?>"><?php echo csrf_field(); ?><button
                            class="px-3 py-2 bg-chalk/10 border border-chalk/20 hover:border-clay rounded transition-colors">Leave</button>
                    </form>
                </div>
            </div>
        </header>
        <div class="relative flex-1 min-h-0 bg-chalk px-2 py-2 sm:px-4 sm:py-3">
            <div class="mx-auto flex h-full max-w-275 items-center justify-center">
                <div class="relative aspect-4/3 w-full max-h-full overflow-hidden rounded-xl border border-chalk/10 bg-[#f6f1ea] shadow-[0_0_0_1px_rgba(255,255,255,0.04)]">
                    <canvas id="canvas" class="block h-full w-full touch-none"></canvas>
                </div>
            </div>
        </div>
        <div class="border-t border-chalk/10 bg-ink/95 px-4 py-3">
            <div id="ai-result" class="hidden">
                <div id="ai-status" class="mb-2 text-sm text-chalk">Done!</div>
                <img id="ai-image" class="max-h-[420px] w-full rounded-lg border border-chalk/10 object-contain bg-[#f6f1ea]" alt="AI polished drawing" hidden>
                <div class="mt-3 flex gap-2">
                    <a id="download-ai" class="hidden rounded border border-chalk/20 bg-chalk/10 px-3 py-2 text-sm text-chalk hover:border-clay" href="#" download="ai-finished-drawing.png">Download PNG</a>
                    <button id="retry-ai" type="button" class="hidden rounded border border-chalk/20 bg-chalk/10 px-3 py-2 text-sm text-chalk hover:border-clay">Retry AI Generation</button>
                </div>
                <p id="ai-error" class="mt-2 hidden text-sm text-red-300"></p>
            </div>
        </div>
    </div>
    <script>window.DRAWING_CONFIG = { code: <?php echo json_encode($session->code, 15, 512) ?>, csrf: <?php echo json_encode(csrf_token(), 15, 512) ?>, reverb: { key: <?php echo json_encode(env('REVERB_APP_KEY'), 15, 512) ?>, host: <?php echo json_encode(env('REVERB_HOST', '127.0.0.1'), 512) ?>, port: <?php echo json_encode((int) env('REVERB_PORT', 8080), 512) ?>, scheme: <?php echo json_encode(env('REVERB_SCHEME', 'http'), 512) ?> }, background: <?php echo json_encode($session->background ? asset('storage/' . $session->background->path) : null, 15, 512) ?>, meIp: <?php echo json_encode(request()->ip(), 15, 512) ?>, initialStrokes: <?php echo json_encode($initialStrokes->map(fn($x) => ['points' => $x->points, 'color' => $x->color, 'size' => $x->size])->values()) ?> };</script>
<script type="module" src="<?php echo e(asset('js/canvas.js')); ?>"></script><?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/fis006/Downloads/collaborative-drawing/resources/views/session.blade.php ENDPATH**/ ?>