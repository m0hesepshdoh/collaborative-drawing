<?php
use App\Http\Controllers\{AdminAuthController,AdminController,BackgroundController,BotPatternController,LandingController,SessionController};
use Illuminate\Support\Facades\{Broadcast,Route};

require base_path('routes/channels.php');

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/broadcasting/auth', function () { return Broadcast::auth(request()); })->middleware('session.broadcast');

Route::middleware('not.banned')->group(function () {
    Route::post('/session/create', [SessionController::class, 'create'])->name('session.create');
    Route::post('/session/bot', [SessionController::class, 'playWithBot'])->name('session.bot');
    Route::post('/session/join', [SessionController::class, 'join'])->name('session.join');
    Route::get('/session/{code}', [SessionController::class, 'show'])->name('session.show');
    Route::post('/session/{code}/leave', [SessionController::class, 'leave'])->name('session.leave');
    Route::post('/session/{code}/report', [SessionController::class, 'report'])->name('session.report');
    Route::post('/session/{code}/clear', [SessionController::class, 'clear'])->name('session.clear');
    Route::post('/session/{code}/record-stroke', [SessionController::class, 'recordStroke'])->name('session.recordStroke');
    Route::post('/session/{code}/heartbeat', [SessionController::class, 'heartbeat'])->name('session.heartbeat');
});

$adminPath = trim(config('admin.path'), '/');
Route::middleware('admin.ip')->prefix($adminPath)->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
Route::middleware(['admin.ip','admin.auth'])->prefix($adminPath)->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::delete('/session/{id}', [AdminController::class, 'deleteSession'])->name('admin.session.delete');
    Route::delete('/sessions/all', [AdminController::class, 'deleteAllSessions'])->name('admin.sessions.deleteAll');
    Route::delete('/session/{id}/player/{playerId}', [AdminController::class, 'removePlayer'])->name('admin.player.remove');
    Route::get('/backgrounds', [BackgroundController::class, 'index'])->name('backgrounds.index');
    Route::post('/backgrounds', [BackgroundController::class, 'store'])->name('backgrounds.store');
    Route::delete('/backgrounds/{background}', [BackgroundController::class, 'destroy'])->name('backgrounds.destroy');
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/bans', [AdminController::class, 'bans'])->name('admin.bans');
    Route::post('/ban', [AdminController::class, 'ban'])->name('admin.ban');
    Route::delete('/ban/{id}', [AdminController::class, 'unban'])->name('admin.unban');
    Route::get('/bot-patterns', [BotPatternController::class, 'index'])->name('admin.botPatterns.index');
    Route::get('/bot-patterns/{id}/preview', [BotPatternController::class, 'preview'])->name('admin.botPatterns.preview');
    Route::delete('/bot-patterns/{id}', [BotPatternController::class, 'destroy'])->name('admin.botPatterns.destroy');
    Route::get('/recordings', [BotPatternController::class, 'recordings'])->name('admin.recordings.index');
    Route::post('/recordings/{id}/approve', [BotPatternController::class, 'approve'])->name('admin.recordings.approve');
    Route::delete('/recordings/{id}', [BotPatternController::class, 'reject'])->name('admin.recordings.reject');
});
