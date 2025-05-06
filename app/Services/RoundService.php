<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Transaction;
use Illuminate\Support\Collection;

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
        $currentSeat = $this->positionService->getCurrentSeat($round->hand);
        $currentSeatAction = Action::where('round_id', $round->id)->where('seat_id', $currentSeat->id)->latest()->first();
        $previousNonpassiveAction = $this->previousNonpassiveActionForCurrentNonFoldedPlayers($round);

        if ($previousNonpassiveAction) { #TODO consider off by one - if the first player raised and everyone called then this might still trigger
            //TODO  Edge case: the player's all in is less than the last bet/raise amount. Need to look for older significant nonpassive actions
            if ($currentSeatAction->action_type === 'allin') { 
                return false;
            } else {
                return false; // If there is a non-passive action in the current lap (might be off by one), the round is NOT complete
            } 
        } #TODO consider how 'bet' from SB and BB impact this- might not need special handling since they count as nonpassive actions

        // the other non-passive cases are handled by the previousNonpassiveActionForCurrentNonFoldedPlayers method if statement
        $passive_actions = ['check', 'fold', 'call'];
        if (in_array($currentSeatAction->action_type, $passive_actions)) {
            $round->update(['is_complete' => true]);
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

    /**
     * Check if all players have checked or folded in the current lap of the round.
     * If not return the last action of the player who has not checked or folded or called.
     * 
     * @param mixed $round
     * @return null|Action
     */
    protected function previousNonpassiveActionForCurrentNonFoldedPlayers($round)
    {   
        $activeSeatHandsCount = $this->getActiveSeatHandsCount($round);
        $actions = Action::where('round_id', $round->id)->orderBy('created_at', 'desc')->get();
        return $this->findNonPassiveAction($actions, $activeSeatHandsCount);
    }

    public function getActiveSeatHandsCount($round)
    {
        $round->load('hand');
        $occupiedSeats = $round->hand->table()->occupiedSeats()->where('status', 'active'); #TODO the count might be off by one, it shouldn't include the player who is acting now
        return $occupiedSeats->map(function ($seat) { // gets the amount of active seat hands in the round who haven't folded yet
            return $seat->seatHand()->where('status', 'active')->first();
        })->count();
    }

    /**
     * Find the first non-passive action in the collection
     * 
     * @param Collection $actions Collection of actions
     * @param int $activeSeatHandsCount Count of active seat hands
     * @return Action|null Non-passive action or null if none found
     */
    protected function findNonPassiveAction($actions, $activeSeatHandsCount)
    {
        $passiveActionTypes = ['check', 'fold', 'call'];
        
        while($actions->isNotEmpty() && $activeSeatHandsCount > 0) {
            $action = $actions->shift();
            if (in_array($action->action_type, $passiveActionTypes)) {
                $activeSeatHandsCount--;
            } else {
                return $action; // Return the non-passive action found
            }
        }
        
        return null; // No non-passive action found
    }
}
