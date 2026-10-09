<?php

// Registers stateless, CSRF-free HTTP routes used by benchmark clients.
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/benchmark/time', [SessionController::class, 'benchmarkTime'])->name('benchmark.time');
Route::post('/benchmark/{code}/stroke', [SessionController::class, 'broadcastBenchmarkStroke'])->name('benchmark.stroke');