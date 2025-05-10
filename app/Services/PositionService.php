<?php

namespace App\Services;

use App\Models\Seat;

class PositionService
{
    /**
     * Get the current seat of the player whose turn it is to act
     */
    public function getCurrentSeat($hand)
    {
        
        # TODO this doesn't work as a substitute for some reason????
        
        $lastAction = $this->getLastAction($hand);
        $lastAction?->load('seat');
        return $lastAction ? $lastAction->seat->getNextActive() : Seat::with('player')->find($hand->big_blind_id); // no action taken yet - preflop
        
        /*
        $activeSeatHands = $hand->seatHands()->where('status', 'active')->with('seat.player')->get(); // loading in player for getNextActive()

        $lastAction = $this->getLastAction($hand);
        $lastSeat = $lastAction ? $lastAction->seat_id : $hand->big_blind_id; #TODO Won't work for 2 players

        foreach ($activeSeatHands as $seatHand) {
            if ($seatHand->seat_id === $lastSeat) { #TODO if last seat went allin, it won't be active and won't be found in activeseathands
                return $seatHand->seat->getNextActive();
            }
        }
        
        \Log::warning('Last seat not found among active seats', [
            'hand_id' => $hand->id,
            'last_seat_id' => $lastSeat,
            'active_seat_ids' => $activeSeatHands->pluck('seat_id')->toArray()
        ]);
        // If no active seat hands are found, return null
        return null;
        */
    }

    public function getLastRound($hand)
    {
        return $hand->rounds()->latest()->first();
    }

    /**
     * Get the last action taken in the latest round of the hand
     */
    public function getLastAction($hand)
    {
        $lastRound = $this->getLastRound($hand);
        return $lastRound ? $lastRound->actions()->latest()->first() : null;
    }
}
