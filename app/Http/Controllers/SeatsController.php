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
    public function join(Seat $seat)
    {
        // Check if seat is available
        if ($seat->user_id) {
            return back()->with('error', 'This seat is already taken.');
        }
        
        // Check if table is open
        if ($seat->table->status !== 'open') {
            return back()->with('error', 'This table is closed and not accepting new players.');
        }
        
        // Check if user already has a seat at this table
        $existingSeat = Seat::where('table_id', $seat->table_id)
            ->where('user_id', Auth::id())
            ->first();
            
        if ($existingSeat) {
            return back()->with('error', 'You already have a seat at this table.');
        }
        
        // Assign seat to user
        $seat->user_id = Auth::id();
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
    public function leave(Seat $seat)
    {
        // Check if the user actually owns this seat
        if ($seat->user_id !== Auth::id()) {
            return back()->with('error', 'You cannot leave a seat that is not yours.');
        }
        
        // Free up the seat
        $seat->user_id = null;
        $seat->save();

        // Broadcast the seat update to all listeners
        broadcast(new TableSeatUpdated($seat->table_id, $seat));
        
        return back()->with('success', 'You have left the table.');
    }
}