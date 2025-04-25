<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Events\TableSeatUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeatsController extends Controller
{
    /**
     * Join a seat at a table.
     */
    public function join(Seat $seat, Request $request)
    {
        // Check if seat is available
        if ($seat->user_id || $seat->guest_session) {
            return back()->with('error', 'This seat is already taken.');
        }
        
        // Check if table is open
        if ($seat->table->status !== 'open') {
            return back()->with('error', 'This table is closed and not accepting new players.');
        }
        

        // Determine if user is authenticated
        $isAuth = Auth::check();

        // Common variables
        $guestSessionId = $request->session()->getId();

        // Set user info
        if ($isAuth) {
            $userId = Auth::id();
            $userName = Auth::user()->name;
        } else {
            $userId = 'guest_' . uniqid();
            $userName = $request->input('guest_name', 'Guest_' . substr($userId, -5));

            // Store guest session data
            $request->session()->put('guest_name', $userName);
        }

        // Check for existing seat
        $existingSeat = Seat::where('table_id', $seat->table_id)
            ->when($isAuth, fn($q) => $q->where('user_id', $userId))
            ->when(!$isAuth, fn($q) => $q->where('guest_session', $guestSessionId))
            ->first();

        if ($existingSeat) {
            return back()->with('error', 'You already have a seat at this table.');
        }

        // Assign new seat
        $seat->user_id = $isAuth ? $userId : null;
        $seat->guest_name = !$isAuth ? $userName : null;
        $seat->guest_session = !$isAuth ? $guestSessionId : null;
        $seat->save();
        
        // Load the user relationship to include in the broadcast
        $seat->load('user');
        
        // Broadcast the seat update to all listeners
        broadcast(new TableSeatUpdated($seat->table_id, $seat));
        
        return back()->with('success', 'You have successfully joined the table!');
    }
    
    /**
     * Leave a seat at a table.
     */
    public function leave(Seat $seat, Request $request)
    {
        $isAuth = Auth::check();
        $guestSessionId = $request->session()->getId();
        
        // Check ownership based on user type
        $canLeave = false;
        if ($isAuth && $seat->user_id === Auth::id()) {
            $canLeave = true;
        } elseif (!$isAuth && $seat->guest_session === $guestSessionId) {
            $canLeave = true;
        }
        
        if (!$canLeave) {
            return back()->with('error', 'You cannot leave a seat that is not yours.');
        }
        
        // Free up the seat
        $seat->user_id = null;
        $seat->guest_name = null;
        $seat->guest_session = null;
        $seat->save();

        // Broadcast the seat update to all listeners
        broadcast(new TableSeatUpdated($seat->table_id, $seat));
        
        return back()->with('success', 'You have left the table.');
    }
}