<?php

namespace App\Services;

use App\Models\Transaction;

class RoundService
{
    protected $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Create a new round for the hand or mark the hand as complete if no more rounds are available.
     */
    public function createNextRound($hand)
    {
        $nextRound = $this->getNextRound($this->positionService->getLastRound($hand)->type);

        if ($nextRound === null) {
            $hand->update(['is_complete' => true]);
            return;
        }

        $hand->rounds()->create([ // hand_id is automatically set by Eloquent
            'type' => $nextRound,
            'is_complete' => false,
        ]);
    }

    protected function getNextRound($currentRoundType)
    {
        // TODO make sure to update community cards and pot size - maybe interconnect with DeckService later for deck tracking
        switch ($currentRoundType) {
            case 'preflop':
                return 'flop';
            case 'flop':
                return 'turn';
            case 'turn':
                return 'river';
            case 'river':
                return null; // No next round after river
            default:
                throw new \Exception('Invalid round type.');
        }
    }

    /**
     * Check if the current round is finished and handle the logic accordingly.
     */
    public function checkRoundFinish($round) 
    {
        switch ($round->type) {
            case 'preflop':
                return $this->checkPreflopFinish($round);
            case 'flop':
                return $this->checkFlopFinish($round);
            case 'turn':
                return $this->checkTurnFinish($round);
            case 'river':
                return $this->checkRiverFinish($round);
            default:
                throw new \Exception('Invalid round type.');
        }
    }

    protected function checkPreflopFinish($round) 
    {
        #TODO as well as the other ones...
    }

    protected function checkFlopFinish($round) 
    {
        #TODO as well as the other ones...
    }

    protected function checkTurnFinish($round) 
    {
        #TODO as well as the other ones...
    }

    protected function checkRiverFinish($round) 
    {
        #TODO as well as the other ones...
    }
}
