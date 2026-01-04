<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Terminal;
use App\Http\Controllers\TerminalWebSocketController;

Route::get('/', Terminal::class);

// Interactive Terminal API Routes
Route::prefix('api/terminal')->group(function () {
    Route::post('/start', [TerminalWebSocketController::class, 'startSession']);
    Route::post('/input', [TerminalWebSocketController::class, 'sendInput']);
    Route::post('/resize', [TerminalWebSocketController::class, 'resize']);
    Route::post('/close', [TerminalWebSocketController::class, 'closeSession']);
});
