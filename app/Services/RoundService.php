<?php

namespace App\Services;

use App\Models\Action;
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
        $nextSeat = $currentSeat->getNextActive(); // tracks by seatHand status - allin's excluded
        $nextSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $nextSeat->id)->latest()->first();
        $previousNonpassiveAction = $this->previousNonpassiveActionForCurrentNonFoldedPlayers($round);
        $passive_actions = ['check', 'fold', 'call']; 
        $prevAction = $nextSeatPreviousAction->action_type;

        if (!$nextSeatPreviousAction || $prevAction === 'bet') { // in pre-flop, bet is considered passive, since only SB and BB buy-ins are possible bets
            return false; // If there hasn't been a next action, every player has not made a move - the round is not complete
        }

        if ($previousNonpassiveAction && (in_array($prevAction, $passive_actions)) || // next seats move was passive
            ($prevAction === 'raise' && $previousNonpassiveAction->amount > $nextSeatPreviousAction->amount)) { // next seat got re-raised
                return false;
        }

        // everyone has played a passive action in the last lap of the round
        $round->update(['is_complete' => true]);
        return $round->is_complete;
    }

    protected function checkFlopFinish($round) 
    {
        $round->load('hand');
        $currentSeat = $this->positionService->getCurrentSeat($round->hand);
        $nextSeat = $currentSeat->getNextActive(); // tracks by seatHand status - allin's excluded
        $nextSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $nextSeat->id)->latest()->first();
        $previousNonpassiveAction = $this->previousNonpassiveActionForCurrentNonFoldedPlayers($round);
        $passive_actions = ['check', 'fold', 'call']; 
        $prevAction = $nextSeatPreviousAction->action_type;

        if (!$nextSeatPreviousAction) {
            return false; // If there hasn't been a next action, every player has not made a move - the round is not complete
        }

        if ($previousNonpassiveAction && (in_array($prevAction, $passive_actions)) || // next seats move was passive
            ($prevAction === 'raise' && $previousNonpassiveAction->amount > $nextSeatPreviousAction->amount)) { // next seat got re-raised
                return false;
        }

        // everyone has played a passive action in the last lap of the round
        $round->update(['is_complete' => true]);
        return $round->is_complete;
    }

    protected function checkTurnFinish($round) 
    {
        $round->load('hand');
        $currentSeat = $this->positionService->getCurrentSeat($round->hand);
        $nextSeat = $currentSeat->getNextActive(); // tracks by seatHand status - allin's excluded
        $nextSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $nextSeat->id)->latest()->first();
        $previousNonpassiveAction = $this->previousNonpassiveActionForCurrentNonFoldedPlayers($round);
        $passive_actions = ['check', 'fold', 'call']; 
        $prevAction = $nextSeatPreviousAction->action_type;

        if (!$nextSeatPreviousAction) {
            return false; // If there hasn't been a next action, every player has not made a move - the round is not complete
        }

        if ($previousNonpassiveAction && (in_array($prevAction, $passive_actions)) || // next seats move was passive
            ($prevAction === 'raise' && $previousNonpassiveAction->amount > $nextSeatPreviousAction->amount)) { // next seat got re-raised
                return false;
        }

        // everyone has played a passive action in the last lap of the round
        $round->update(['is_complete' => true]);
        return $round->is_complete;
    }

    protected function checkRiverFinish($round) 
    {
        $round->load('hand');
        $currentSeat = $this->positionService->getCurrentSeat($round->hand);
        $nextSeat = $currentSeat->getNextActive(); // tracks by seatHand status - allin's excluded
        $nextSeatPreviousAction = Action::where('round_id', $round->id)->where('seat_id', $nextSeat->id)->latest()->first();
        $previousNonpassiveAction = $this->previousNonpassiveActionForCurrentNonFoldedPlayers($round);
        $passive_actions = ['check', 'fold', 'call']; 
        $prevAction = $nextSeatPreviousAction->action_type;

        if (!$nextSeatPreviousAction) {
            return false; // If there hasn't been a next action, every player has not made a move - the round is not complete
        }

        if ($previousNonpassiveAction && (in_array($prevAction, $passive_actions)) || // next seats move was passive
            ($prevAction === 'raise' && $previousNonpassiveAction->amount > $nextSeatPreviousAction->amount)) { // next seat got re-raised
                return false;
        }

        // everyone has played a passive action in the last lap of the round
        $round->update(['is_complete' => true]);
        return $round->is_complete;
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

    /**
     * Get the count of active seat hands in the round.
     * 
     * @param mixed $round
     * @return int Count of active seat hands
     */
    public function getActiveSeatHandsCount($round)
    {
        $round->load('hand');
        $occupiedSeats = $round->hand->table()->occupiedSeats()->where('status', 'active');
        return $occupiedSeats->map(function ($seat) { // gets the amount of active seat hands in the round who haven't folded or allined yet
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
        
        // An allin that is less than the last bet/raise amount is considered a passive action - essentially a call
        $most_significant_action = null;

        while($actions->isNotEmpty() && $activeSeatHandsCount > 0) {
            $action = $actions->shift();

            if (in_array($action->action_type, $passiveActionTypes)) {
                if ($most_significant_action ? $action->amount > $most_significant_action->amount : true) {
                    $most_significant_action = $action;
                }
                $activeSeatHandsCount--;
            }
        }
        
        return $most_significant_action;
    }
}
