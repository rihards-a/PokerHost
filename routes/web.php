<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TablesController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

// testing
use App\Events\TestBoxEvent;
Route::get('/12box', function() {
    return Inertia::render('12boxes');
});
Route::post('/12box/update', function (Request $request) {
    try {
        $cardId = $request->input('card_id');
        event(new TestBoxEvent($cardId));
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Event dispatch failed'], 500);
    }
});
// endtesting

Route::get('/', [TablesController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [TablesController::class, 'dashboard'])->name('dashboard');
    
    // Table management
    Route::post('/tables', [TablesController::class, 'store'])->name('tables.store');
    Route::post('/tables/{table}/toggle-status', [TablesController::class, 'toggleStatus'])->name('tables.toggle-status');
    Route::delete('/tables/{table}', [TablesController::class, 'destroy'])->name('tables.destroy');
   
    // Table view/join
    Route::get('/tables/{table}', [TablesController::class, 'show'])->name('tables.show');
    Route::post('/tables/{table}/join', [TablesController::class, 'join'])->name('tables.join');
    Route::post('/tables/{table}/leave', [TablesController::class, 'leave'])->name('tables.leave');
});

// Prebuild routes:
/*Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
