<?php

namespace App\Services;

use App\Models\Action;
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

    #TODO make sure this gets called one by one if all active players have all-ined
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
        $round->load('hand');
        $nextSeat = $this->positionService->getCurrentSeat($round->hand);
        $nextSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $nextSeat->id)->latest()->first();
        $currentSeat = $nextSeat->previousActive();
        $currentSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $currentSeat->id)->latest()->first();
        switch ($nextSeatPreviousAction->action_type) {
            case 'fold':
                $round->update(['is_complete' => true]);
                break;
            case 'check':
                // match with fold, in case the player didn't bother to even check, validation happens inside the action service
                if ($currentSeatPreviousAction && ($currentSeatPreviousAction->action_type === 'check' || $currentSeatPreviousAction->action_type === 'fold')) {
                    // check if all players have checked or folded
                    $allCheckedOrFolded = Action::where('round_id', $round->id)
                        ->whereIn('action_type', ['check', 'fold'])->count() ===
                         $round->hand->table()->occupiedSeats()->where('status', 'active')->count();
                    if ($allCheckedOrFolded) {
                        $round->update(['is_complete' => true]);
                        break;
                    }
                }
                break;
            case 'call':
                // Handle call action
                break;
            case 'raise':
                // Handle raise action
                break;
            case 'bet':
                // Handle bet action
                break;
            case 'allin':
                // Handle allin action
                break;
            default:
                throw new \Exception('Invalid action type.');
        }
        return $round->is_complete;
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
