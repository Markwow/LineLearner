<?php

use App\Http\Controllers\Admin\InviteController;
use App\Http\Controllers\Admin\ScriptController as AdminScriptController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\ScriptController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Registration is invite-only — the code is checked in the controller.
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [ScriptController::class, 'index'])->name('scripts.index');
    Route::post('/scripts', [ScriptController::class, 'store'])->name('scripts.store');
    Route::get('/scripts/{script}', [ScriptController::class, 'show'])->name('scripts.show');
    Route::put('/scripts/{script}', [ScriptController::class, 'update'])->name('scripts.update');
    Route::delete('/scripts/{script}', [ScriptController::class, 'destroy'])->name('scripts.destroy');

    Route::post('/scripts/{script}/lines/{lineIndex}/recording', [RecordingController::class, 'store'])
        ->name('recordings.store');
    Route::delete('/scripts/{script}/lines/{lineIndex}/recording', [RecordingController::class, 'destroy'])
        ->name('recordings.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/scripts', [AdminScriptController::class, 'index'])->name('scripts.index');
    Route::put('/scripts/{script}/owner', [AdminScriptController::class, 'updateOwner'])->name('scripts.owner');
    Route::post('/scripts/claim-unowned', [AdminScriptController::class, 'claimUnowned'])->name('scripts.claim');
    Route::delete('/scripts/{script}', [AdminScriptController::class, 'destroy'])->name('scripts.destroy');

    Route::get('/invites', [InviteController::class, 'index'])->name('invites.index');
    Route::post('/invites', [InviteController::class, 'store'])->name('invites.store');
    Route::delete('/invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');
});
