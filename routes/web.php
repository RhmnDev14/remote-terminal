<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Terminal;
use App\Http\Controllers\TerminalController;

Route::get('/', Terminal::class);

// Terminal API Routes (no auth for stateless operation)
Route::prefix('api/terminal')->group(function () {
    Route::post('/connect', [TerminalController::class, 'connect']);
    Route::post('/input', [TerminalController::class, 'input']);
    Route::post('/read', [TerminalController::class, 'read']);
    Route::post('/resize', [TerminalController::class, 'resize']);
    Route::post('/disconnect', [TerminalController::class, 'disconnect']);
    Route::post('/restore', [TerminalController::class, 'restore']);
});
