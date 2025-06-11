<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Hand;
use App\Models\Player;
use App\Services\PokerStateService;
use App\Services\ActionService;
use App\Services\PositionService;
use App\Services\RoundService;
use App\Services\HandService;
use App\Events\TableStateChanged;
use App\Events\HandFinished;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActionController extends Controller
{
    protected $actionService, $positionService, $roundService, $handService, $pokerStateService;

    public function __construct(ActionService $actionService, PositionService $positionService, RoundService $roundService, HandService $handService, PokerStateService $pokerStateService)
    {
        $this->pokerStateService = $pokerStateService;
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
                    $hand->refresh();
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
                    if ($roundFinished) {\Log::debug('round advanced... ', [$roundFinished->type, json_decode($hand->community_cards)]);}
                    broadcast(new TableStateChanged($table->id, $this->pokerStateService->getTableState($table)));
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
        $currentSeatPlayer = $this->positionService->getCurrentSeat($hand)?->player;

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

    public function getOwnPlayerData(Table $table, Request $request)
    {
        $guestSessionId = $request->session()->getId();

        // Try authenticated user
        if (Auth::check()) {
            $player = Player::where('user_id', Auth::id())
                ->whereHas('seats', function ($query) use ($table) {
                $query->where('table_id', $table->id);
            })->first();
        }
        else {
            // Fallback to guest
            $player = $guestSessionId ? Player::where('guest_session', $guestSessionId)
            ->whereHas('seats', function ($query) use ($table) {
            $query->where('table_id', $table->id);
        })->first() : null;
        }

        // If no player found, 204 No Content
        if (!$player) {
            return response()->json(null, 204);
        }

        $seat = $player->seats()->where('table_id', $table->id)->first();

        if (!$seat) {
            return response()->json(['error' => 'Player not seated at this table'], 400);
        }

        $currentSeatHand = $seat->seatHand()->latest()->with(['hand'])->first();

        $cards_dealt = false;
        if (!$currentSeatHand?->hand->is_complete) {
            $cards_dealt = $currentSeatHand?->hand->rounds()->exists();
        }

        $card1 = $cards_dealt ? $currentSeatHand->card1 : null;
        $card2 = $cards_dealt ? $currentSeatHand->card2 : null;       

        // Return only the specified attributes
        return response()->json([
            'player' => [
                'active'        => $player->active,
                'stack'         => $player->balance ? $player->balance : 0,
                'name'          => $guestSessionId ? $player->guest_name : Auth::user()->name,
                'id'            => $player->id,
            ],
            'cards' => [
                'card1' => $card1,
                'card2' => $card2,
            ],
        ], 200);
    }
}
