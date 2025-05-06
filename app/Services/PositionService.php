<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;

class PositionService extends ServiceProvider
{
    /**
     * Get the current seat of the player whose turn it is to act
     */
    public function getCurrentSeat($hand)
    {
        $activeSeatHands = $hand->seatHands()->whereHas('seat.player', function ($query) {
            $query->where('status', 'active');
        })->with('seat')->get();

        $lastRound = $hand->rounds()->latest()->first();
        $lastAction = $lastRound ? $lastRound->actions()->latest()->first() : null;
        $lastSeat = $lastAction ? $lastAction->seat_id : $hand->big_blind_id; #TODO Won't work for 2 players

        foreach ($activeSeatHands as $seatHand) {
            if ($seatHand->seat_id === $lastSeat) {
                return $seatHand->seat->nextActive();
            }
        }
        // If no active seat hands are found, return null
        return null;
    }
}
