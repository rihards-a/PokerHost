<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;
use App\Models\Transaction;
use App\Models\Action;

class ActionService extends ServiceProvider
{
    protected $positionService, $transactionService;

    public function __construct(PositionService $positionService, TransactionService $transactionService)
    {
        $this->positionService = $positionService;
        $this->transactionService = $transactionService;
    }

    /**
     * Process and validate a player action (bet, call, raise, check, fold)
     */
    #TODO IMPLEMENT LOGIC FOR ACTIONS IF THE ROUND IS LOOPING BECAUSE OF RERAISES - maybe create method in transaction service
    #TODO collect all actions in round from player and check total amount then make more complex calculations
    #TODO add tracker for has_checked and has_bet ?
    public function processAction($hand, $currentSeat, $actionType, $amount) {
        $player = $currentSeat->player;
        $lastAction = $this->positionService->getLastAction($hand);

        // Validate action based on the current game state
        if ($amount > $player->balance || $amount <= 0) {
            throw new \Exception('Insufficient balance for this action.');
        }
        switch ($actionType) {
            case 'bet':
                if ($lastAction && $lastAction->action_type !== 'check') {
                    throw new \Exception('Invalid - you can only bet if no one has acted yet.');
                }
                break;
            case 'call':
                if (!$lastAction || $amount !== $lastAction->amount) {
                    throw new \Exception('Invalid call amount - must be equal to the previous amount.'); 
                    // Otherwise it's an all-in or first move - which is a check or bet
                }
                break;
            case 'raise':
                if ($amount < $lastAction->amount * 2) {
                    throw new \Exception('Invalid raise amount - must be at least double the current bet.');
                }
                break;
            case 'check':
                $amount = 0; // No amount is needed for a check
                break;
            case 'fold':
                $amount = 0; // No amount is needed for a fold
                break;
            case 'allin':
                $amount = $player->balance; // All-in is the player's entire balance
                break;
            default:
                throw new \Exception('Invalid action type.');
        }

        // Process the action and update the game state accordingly
        $action = new Action([
            'round_id' => $this->positionService->getLastRound($hand)->id,
            'seat_id' => $currentSeat->id,
            'action_type' => $actionType,
            'amount' => $amount,
        ]);
        // If the action is a bet, call, raise, or all-in, we need to update the player's balance and the pot size
        if ($actionType !== 'fold' || $actionType !== 'check') {
            $this->transactionService->betTransaction($hand, $player, $amount);
        }

        #TODO update the round status - complete or not, use the RoundService to implement this
    }

    public function getAvailableActions($hand) {
        $lastAction = $this->positionService->getLastAction($hand);
        $availableActions = [];

        #TODO IMPLEMENT LOGIC FOR CHECKING IF THE ROUND IS LOOPING BECAUSE OF RERAISES

        return $availableActions;
    }
}
