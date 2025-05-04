<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Events\TableSeatUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatsController extends Controller
{
    /**
     * Join a seat at a table.
     */
    public function join(Seat $seat, Request $request)
    {
        // Check if seat is available
        if ($seat->isTaken()) {
            return back()->with('error', 'This seat is already taken.');
        }
        
        // Check if table is open
        if ($seat->table->status !== 'open') {
            return back()->with('error', 'This table is closed and not accepting new players.');
        }
        
        $isAuth = Auth::check();
        $guestSessionId = $request->session()->getId();

        // Set user info
        if ($isAuth) {$userId = Auth::id();}

        // Check for existing seat
        $existingSeat = Seat::where('table_id', $seat->table_id)
        ->where(fn($q) => $isAuth
            ? $q->where('user_id', $userId)
            : $q->where('guest_session', $guestSessionId)
        )->exists();

        if ($existingSeat) {
            return back()->with('error', 'You already have a seat at this table.');
        }

        // Set user info
        $userName = $request->session()->get('guest_name');
        if (! $isAuth) {
            $userName = $request->input('guest_name') // If the user provided a name
                ?: 'Guest_' . substr(uniqid(), -5);
    
            // Persist the guest name for future requests
            $request->session()->put('guest_name', $userName);
        }
        
        $player = $seat->player;
        DB::transaction(function () use ($player, $isAuth, $guestSessionId, $userName, $seat, $userId) {
            $player->update([
                'user_id'       => $isAuth ? $userId : null,
                'guest_name'    => $isAuth ? null : $userName,
                'guest_session' => $isAuth ? null : $guestSessionId,
            ]);
    
            // Reload any relations you need to include in the broadcast
            $seat->load('player.user');
    
            broadcast(new TableSeatUpdated($seat->table_id, $seat));
        });
        
        return back()->with('success', 'You have successfully joined the table!');
    }
    
    /**
     * Leave a seat at a table.
     */
    public function leave(Seat $seat, Request $request)
    {
        $player = $seat->player ?? abort(404, 'Player not found');

        $isAuth = Auth::check();
        $guestSessionId = $request->session()->getId();
        
        // Check ownership based on user type
        $canLeave = false;
        if ($isAuth && $seat->player->user_id === Auth::id()) {
            $canLeave = true;
        } elseif (!$isAuth && $seat->player->guest_session === $guestSessionId) {
            $canLeave = true;
        }
        
        if (!$canLeave) {
            return back()->with('error', 'You cannot leave a seat that is not yours.');
        }
        
        // Use a transaction to ensure data integrity
        DB::transaction(function () use ($player, $seat) {
            $player->update([
                'user_id'       => null,
                'guest_name'    => null,
                'guest_session' => null,
            ]);
            // Broadcast the seat update to all listeners
            broadcast(new TableSeatUpdated($seat->table_id, $seat));
        });
        
        return back()->with('success', 'You have left the table.');
    }
}