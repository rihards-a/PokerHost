<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

// testing
use App\Events\TestBoxEvent;
Route::get("/spawn-box/{something}", function ($something) {
    event(new TestBoxEvent("$something"));
    return response()->json(['success' => true]);
});

Route::get('/12box', function() {
    return Inertia::render('12boxes');
});
Route::post('/12box/update', function (Request $request) {
    try {
        $cardId = $request->input('card_id');
        Log::info('Received card_id: ' . $cardId);
        event(new TestBoxEvent($cardId));
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Event dispatch failed: ' . $e->getMessage());
        return response()->json(['error' => 'Event dispatch failed'], 500);
    }
});
// endtesting



// Prebuild routes:
Route::get('/', function () {
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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
