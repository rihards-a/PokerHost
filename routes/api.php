<?php

use App\Http\Controllers\TablesController;
use App\Http\Controllers\SeatsController;
use App\Http\Controllers\HandController;
use App\Http\Controllers\ActionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/tables/{table}/toggle-status', [TablesController::class, 'toggleStatus'])->name('tables.toggle-status');
    Route::delete('/tables/{table}', [TablesController::class, 'destroy'])->name('tables.destroy');
});

// Seat management
Route::post('/seats/{seat}/join', [SeatsController::class, 'join'])->name('seats.join');
Route::post('/seats/{seat}/leave', [SeatsController::class, 'leave'])->name('seats.leave');

// Hand management
Route::post('/tables/{table}/start-hand', [HandController::class, 'start'])->name('tables.start-hand');

// Action management
Route::post('/tables/{table}/hands/{hand}/actions', [ActionController::class, 'process'])->name('tables.action.process');
Route::get('/tables/{table}/hands/{hand}/actions', [ActionController::class, 'getAvailableActions'])->name('tables.action.get');
// for receiving player data
Route::get('players/me', [ActionController::class, 'getOwnPlayerData']);
