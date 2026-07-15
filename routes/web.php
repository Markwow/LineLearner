<?php

use App\Http\Controllers\RecordingController;
use App\Http\Controllers\ScriptController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScriptController::class, 'index'])->name('scripts.index');
Route::post('/scripts', [ScriptController::class, 'store'])->name('scripts.store');
Route::get('/scripts/{script}', [ScriptController::class, 'show'])->name('scripts.show');
Route::put('/scripts/{script}', [ScriptController::class, 'update'])->name('scripts.update');
Route::delete('/scripts/{script}', [ScriptController::class, 'destroy'])->name('scripts.destroy');

Route::post('/scripts/{script}/lines/{lineIndex}/recording', [RecordingController::class, 'store'])
    ->name('recordings.store');
Route::delete('/scripts/{script}/lines/{lineIndex}/recording', [RecordingController::class, 'destroy'])
    ->name('recordings.destroy');
