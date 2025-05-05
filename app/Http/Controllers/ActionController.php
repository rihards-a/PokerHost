<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Hand;
use App\Models\Seat;
use App\Models\Action;
use App\Services\ActionService;
use App\Services\PositionService;
use App\Events\PlayerTurnChanged;
use App\Events\RoundAdvanced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
    protected $actionService;
    protected $positionService;

    public function __construct(ActionService $actionService, PositionService $positionService)
    {
        $this->actionService = $actionService;
        $this->positionService = $positionService;
    }

    /**
     * Process and validate a player action (bet, call, raise, check, fold)
     */
    public function process(Request $request, Table $table, Hand $hand)
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'action_type' => 'required|in:bet,call,raise,check,fold,allin',
            'amount' => 'nullable|numeric|min:0|max:1000000',
        ]);
        
        $actionType = $validated['action_type'];
        $amount = $validated['amount'] ?? null;
                
        $currentSeat = $this->positionService->getCurrentSeat($hand);
        
        if (!$currentSeat) {
            return back()->with('error', 'Invalid player turn.');
        }

        $isAuth = Auth::check();
        $isUsersTurn = false;

        if ($isAuth && $currentSeat->player->user_id === Auth::id()) {
            $isUsersTurn = true;
        } elseif (!$isAuth && $currentSeat->player->guest_session === session()->getId()) {
            $isUsersTurn = true;
        }

        if (!$isUsersTurn) {
            return back()->with('error', 'It is not your turn to act.');
        }

        try {
            $result = null;
            DB::transaction(function () use ($hand, $currentSeat, $actionType, $amount, $table, &$result) {
                $result = $this->actionService->processAction($hand, $currentSeat, $actionType, $amount);
       
                // If the round is complete, advance to the next round
                if ($result['round_complete']) {
                    $this->actionService->advanceRound($hand);
       
                    // Finalize the hand if the status is complete
                    if ($hand->status === 'complete') {
                        $this->actionService->finalizeHand($hand, $table);
                    }
                }
            });
            
            // Set up broadcasting after the transaction is committed
            DB::afterCommit(function () use ($hand, $table, $result) {
                $hand->refresh();
                if ($hand->status === 'complete') {
                    broadcast(new HandFinished($table->id, $hand->id)); # TODO pass winners?
                } else {
                    if ($result['round_complete']) {
                        broadcast(new RoundAdvanced($table->id, $hand->current_round));
                    }
                    $nextSeat = $this->positionService->getCurrentSeat($hand)->nextActive();
                    broadcast(new PlayerTurnChanged($table->id, $nextSeat->id));
                }
            });
       
            return back()->with('success', 'Action processed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to process action: ' . $e->getMessage());
        }
    }

    /**
     * Get available actions for the current player
     */
    public function getAvailableActions(Hand $hand)
    {       
        $currentSeat = $this->positionService->getCurrentSeat($hand);

        if (!$currentSeat || !$currentSeat->player) {
            return response()->json(['error' => 'Invalid player turn.'], 400);
        }

        $isAuth = Auth::check();
        $isUsersTurn = false;

        if ($isAuth && $currentSeat->player->user_id === Auth::id()) {
            $isUsersTurn = true;
        } elseif (!$isAuth && $currentSeat->player->guest_session === session()->getId()) {
            $isUsersTurn = true;
        }

        if (!$isUsersTurn) {
            return response()->json(['error' => 'It is not your turn to act.'], 403);
        }

        $actions = $this->actionService->getAvailableActions($hand, $currentSeat);

        return response()->json(['actions' => $actions]);
    }
}
