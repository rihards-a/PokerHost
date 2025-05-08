<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TablesController;
use App\Http\Controllers\SeatsController;
use App\Http\Controllers\HandController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/', [TablesController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [TablesController::class, 'dashboard'])->name('dashboard');
    
    // Table management
    Route::post('/tables', [TablesController::class, 'store'])->name('tables.store');
    Route::post('/tables/{table}/toggle-status', [TablesController::class, 'toggleStatus'])->name('tables.toggle-status');
    Route::delete('/tables/{table}', [TablesController::class, 'destroy'])->name('tables.destroy');
});

// Table view/join
Route::get('/tables/{table}', [TablesController::class, 'show'])->name('tables.show');

// Seat management
Route::post('/seats/{seat}/join', [SeatsController::class, 'join'])->name('seats.join');
Route::post('/seats/{seat}/leave', [SeatsController::class, 'leave'])->name('seats.leave');

// Hand management
Route::post('/tables/{table}/start-hand', [HandController::class, 'start'])->name('tables.start-hand');

// Action management
Route::post('/tables/{table}/hands/{hand}/actions', [TablesController::class, 'process'])->name('tables.action.process');
Route::get('/tables/{table}/hands/{hand}/actions', [TablesController::class, 'getAvailableActions'])->name('tables.action.get');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
