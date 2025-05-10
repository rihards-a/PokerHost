<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TablesController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/api.php';

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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
