<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Hand;
use App\Models\Player;
use App\Services\ActionService;
use App\Services\PositionService;
use App\Services\RoundService;
use App\Services\HandService;
use App\Events\PlayerTurnChanged;
use App\Events\ActionTaken;
use App\Events\RoundAdvanced;
use App\Events\HandFinished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
    protected $actionService, $positionService, $roundService, $handService;

    public function __construct(ActionService $actionService, PositionService $positionService, RoundService $roundService, HandService $handService)
    {
        $this->actionService = $actionService;
        $this->positionService = $positionService;
        $this->roundService = $roundService;
        $this->handService = $handService;
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

        if ($hand->is_complete) {
            return response()->json(['error' => 'Hand has finished.'], 400);
        }
        
        $actionType = $validated['action_type'];
        $amount = $validated['amount'] ?? null;
                
        $currentSeat = $this->positionService->getCurrentSeat($hand);
        
        if (!$currentSeat) {
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

        try {
            $winners = null;
            $roundFinished = null;
            $action = null;
            DB::transaction(function () use ($hand, $currentSeat, $actionType, $amount, &$winners,  &$roundFinished, &$action) {
                $lastRound = $this->positionService->getLastRound($hand);
                $action = $this->actionService->processAction($hand, $currentSeat, $actionType, $amount);

                \Log::debug('action processed: ', [$action]);

                // If the round is complete, advance to the next round
                if ($this->roundService->checkRoundFinish($lastRound)) {
                    $roundFinished = $lastRound;
                    $this->roundService->createNextRound($hand);
       
                    // Finalize the hand if the status is complete
                    if ($hand->is_complete) {
                        $winners = $this->handService->finalizeHand($hand);
                    }
                }
            });
            
            // Set up broadcasting after the transaction is committed
            DB::afterCommit(function () use ($hand, $table, $winners, $roundFinished, $action) {
                $hand->refresh();
                if ($hand->is_complete) {
                    broadcast(new HandFinished($table->id, $hand->id, $winners));
                } else {
                    if ($roundFinished) {
                        $community_cards = json_decode($hand->community_cards);
                        switch ($roundFinished->type) {
                            case 'preflop':
                                \Log::debug('round advanced... ', [$roundFinished->type, $community_cards]);
                                $cards = array_slice($community_cards, 0, 3);
                                broadcast(new RoundAdvanced($table->id, 'flop', $cards));
                                break;
                            case 'flop':
                                \Log::debug('round advanced... ', [$roundFinished->type, $community_cards]);
                                $cards = array_slice($community_cards, 3, 1);
                                broadcast(new RoundAdvanced($table->id, 'turn', $cards));
                                break;
                            case 'turn':
                                \Log::debug('round advanced... ', [$roundFinished->type, $community_cards]);
                                $cards = array_slice($community_cards, 4, 1);
                                broadcast(new RoundAdvanced($table->id, 'river', $cards));
                                break;
                            case 'river':
                                // taken care of by HandFinished broadcast
                                break;
                        }
                    }
                    $nextSeat = $this->positionService->getCurrentSeat($hand);
                    broadcast(new ActionTaken($table->id, $action));
                    broadcast(new PlayerTurnChanged($table->id, $nextSeat->id));
                }
            });
       
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            // Log the error with full exception context
            \Log::error('Failed to process action', [
                'table_id'    => $table->id ?? null,
                'hand_id'     => $hand->id ?? null,
                'player_id'   => $currentSeat->player->id ?? null,
                'action_type' => $actionType,
                'amount'      => $amount,
                'exception'   => $e,
            ]);
        
            return response()->json([
                'error' => 'Failed to process action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available actions for the current player
     */
    public function getAvailableActions(Table $table, Hand $hand)
    {
        $currentSeatPlayer = $this->positionService->getCurrentSeat($hand)->player;

        if (!$currentSeatPlayer || !$currentSeatPlayer->active) {
            return response()->json(['error' => 'Invalid player turn.'], 400);
        }

        $isAuth = Auth::check();
        $isUsersTurn = false;

        if ($isAuth && $currentSeatPlayer->user_id === Auth::id()) {
            $isUsersTurn = true;
        } elseif (!$isAuth && $currentSeatPlayer->guest_session === session()->getId()) {
            $isUsersTurn = true;
        }

        if (!$isUsersTurn) {
            return response()->json(['error' => 'It is not your turn to act.'], 403);
        }

        $actions = $this->actionService->getAvailableActions($hand, $currentSeatPlayer); #TODO implement this in the action service

        return response()->json(['actions' => $actions]);
    }

    public function getOwnPlayerData(Request $request)
    {
        $guestSessionId = $request->session()->getId();

        // Try authenticated user
        if (Auth::check()) {
            $player = Player::where('user_id', Auth::id())->first();
        }
        else {
            // Fallback to guest
            $player = $guestSessionId ? Player::where('guest_session', $guestSessionId)->first() : null;
        }

        // If no player found, 204 No Content
        if (! $player) {
            return response()->json(null, 204);
        }

        // Return only the specified attributes
        return response()->json([
            'player' => [
                'active'        => $player->active,
                'balance'       => $player->balance ? $player->balance : 0,
                'name'          => $guestSessionId ? $player->guest_name : Auth::user()->name,
                'id'            => $player->id,
            ]
        ], 200);
    }
}
