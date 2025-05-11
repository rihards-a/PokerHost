<?php

namespace App\Services;

use App\Models\seatHand;
use App\Models\Action;
use App\Models\Seat;

class ActionService
{
    protected $positionService, $transactionService, $roundService;

    public function __construct(PositionService $positionService, TransactionService $transactionService, RoundService $roundService)
    {
        $this->positionService = $positionService;
        $this->transactionService = $transactionService;
        $this->roundService = $roundService;
    }

    /**
     * Process and validate a player action (bet, call, raise, check, fold)
     */
    #TODO IMPLEMENT LOGIC FOR ACTIONS IF THE ROUND IS LOOPING BECAUSE OF RERAISES - maybe create method in transaction service
    #TODO collect all actions in round from player and check total amount then make more complex calculations
    #TODO add tracker for has_checked and has_bet ?
    public function processAction($hand, $currentSeat, $actionType, $amount) {
        $player = $currentSeat->player;
        $round = $this->positionService->getLastRound($hand);
        $seatHand = seatHand::where('hand_id', $hand->id)->where('seat_id', $currentSeat->id)->first();
        // $lastAction = $this->positionService->getLastAction($hand);
        $previousNonPassiveAction = $this->roundService->previousNonpassiveActionForCurrentNonFoldedPlayers($round);

        // Validate action based on the current game state
        if ($amount > $player->balance || $amount < 0) {
            throw new \Exception('Insufficient balance for this action.');
        }
        switch ($actionType) {
            case 'bet':
                if ($previousNonPassiveAction) {
                    throw new \Exception('Invalid - you can only bet if no one has acted yet.'); #TODO change these exceptions to responses for incorrect POST requests
                }
                if ($amount == 0) {
                    throw new \Exception('Insufficient balance for this action.');
                }
                break;
            case 'call':
                if (!$previousNonPassiveAction) {
                    throw new \Exception('Invalid call - can only happen after an agressive action.'); 
                    // Otherwise it's an all-in or first move - which is a check or bet
                }
                $amount = $previousNonPassiveAction->amount;
                break;
            case 'raise':
                if (!$previousNonPassiveAction) {
                    throw new \Exception('Invalid - you can only raise if someone has acted.');
                }
                if ($amount < $previousNonPassiveAction->amount * 2) {
                    throw new \Exception('Invalid raise amount - must be at least double the current bet.');
                }
                break;
            case 'check':
                if ($round->type === 'preflop') {
                    $BB = Seat::find($hand->big_blind_id)->id;
                    if ($BB === $currentSeat->id) {
                        if ($currentSeat->actions()->count() === 1) { // if BB has not made an action since the bet, he can check out
                            $amount = 0; // No amount is needed for a check
                            break;
                        }
                    }
                } else if ($previousNonPassiveAction) {
                    throw new \Exception('Invalid - you can only check if noone has acted.');
                }
                $amount = 0; // No amount is needed for a check
                break;
            case 'fold':
                $amount = 0; // No amount is needed for a fold
                $seatHand->update(['status' => 'folded']);
                break;
            case 'allin':
                $amount = $player->balance; // All-in is the player's entire balance
                $seatHand->update(['status' => 'allin']);
                break;
            default:
                throw new \Exception('Invalid action type.');
        }

        // Process the action and update the game state accordingly
        $action = Action::create([
            'round_id' => $this->positionService->getLastRound($hand)->id,
            'seat_id' => $currentSeat->id,
            'action_type' => $actionType,
            'amount' => $amount,
        ]);
        // If the action is a bet, call, raise, or all-in, we need to update the player's balance and the pot size
        if ($actionType !== 'fold' || $actionType !== 'check') {
            $this->transactionService->betTransaction($hand, $player, $amount);
        }

        return $action;
    }

    public function getAvailableActions($hand, $currentPlayer) {
        $currentSeat = $currentPlayer->seats()->where('table_id', $hand->table->id)->first();
        $availableActions = ['fold', 'allin']; // out of 'fold', 'check', 'call', 'raise', 'bet', 'allin'
        $round = $hand->rounds()->latest()->first();
        $nonpassiveAction = $this->roundService->previousNonpassiveActionForCurrentNonFoldedPlayers($round);
        $currentAmount = $this->getTotalBetAmountForCurrentSeatThisRound($currentSeat->id, $round);

        if (!$nonpassiveAction) { // no bet (meaning -> no raise or call or allin), only checks or folds
            $availableActions[] = 'bet';
            $availableActions[] = 'check';
        } else {
            $nonpassiveAmount = $this->getTotalBetAmountForCurrentSeatThisRound($nonpassiveAction->seat->id, $round);
            if (($nonpassiveAmount < $currentAmount + $currentPlayer->balance) && ($nonpassiveAmount != $currentAmount)) {
                $availableActions[] = 'call';
            }
            if ($nonpassiveAmount <= ($currentAmount + $currentPlayer->balance) / 2) {                
                $availableActions[] = 'raise';
            }
            if ($this->positionService->getLastRound($hand)->type === 'preflop') { // edge case: BB on preflop can check on first lap
                $BB = Seat::find($hand->big_blind_id)->id;
                    if ($BB === $currentSeat->id) {
                        if ($currentSeat->actions()->count() === 1) {
                            $availableActions[] = 'check';
                        }
                    }
            }
        }

        return $availableActions;
    }

    public function getTotalBetAmountForCurrentSeatThisRound($seatId, $round) {
        return (int) $round
        ->actions()
        ->where('seat_id', $seatId)
        ->sum('amount');
    }

    public function betSBandBB($hand, $round, $seat, $amount) {
        Action::create([
            'round_id' => $round->id,
            'seat_id' => $seat->id,
            'action_type' => 'bet',
            'amount' => $amount,
        ]);
        $this->transactionService->betTransaction($hand, $seat->player, $amount);
    }
}
