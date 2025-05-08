<?php

namespace App\Services;

class PositionService
{
    /**
     * Get the current seat of the player whose turn it is to act
     */
    public function getCurrentSeat($hand)
    {
        $activeSeatHands = $hand->seatHands()->where('status', 'active')->with('seat')->get();

        $lastAction = $this->getLastAction($hand);
        $lastSeat = $lastAction ? $lastAction->seat_id : $hand->big_blind_id; #TODO Won't work for 2 players

        foreach ($activeSeatHands as $seatHand) {
            if ($seatHand->seat_id === $lastSeat) {
                return $seatHand->seat->getNextActive();
            }
        }
        // If no active seat hands are found, return null
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
        return $lastRound ? $lastRound->actions()->latest()->first() : null;
    }
}
