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
        // 1) find the starting seat
        $last = $this->getLastAction($hand);
        $start = $last ? $last->load('seat')->seat->getNextActive()
               : Seat::with('player')->find($hand->dealer_id)->getNextActive(); // pre-flop - no last actions
    
        // 2) walk the circle until we either find an active SeatHand, or come full-circle
        $current = $start;
        do {
            $seatHand = $current->seatHand()->where('hand_id', $hand->id)->latest()->first();
            if ($seatHand && $seatHand->status === 'active') return $current;
            $current = $current->getNextActive();
        } while ($current->id !== $start->id);
        
        return null;
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
        // orderBy twice to separate SB and BB - they get committed at the same time, but have an incremented ID
        return $lastRound ? $lastRound->actions()->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first() : null;
    }
}
