<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Seat;
use App\Services\HandService;
use App\Services\ActionService;
use App\Services\PokerStateService;
use App\Events\TableStateChanged;
use App\Events\HandStarted;
use App\Events\ActionTaken;
use App\Events\PlayerTurnChanged;
use App\Events\PlayerCardsDealt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HandController extends Controller
{
    protected $handService, $actionService, $pokerStateService;

    public function __construct(HandService $handService, ActionService $actionService, PokerStateService $pokerStateService)
    {
        $this->handService = $handService;
        $this->actionService = $actionService;
        $this->pokerStateService = $pokerStateService;
    }

    /**
     * Start a new hand at the table.
     */
    #TODO deal out BB and SB bets
    public function start(Table $table)
    {
        if (Auth::id() !== $table->host_id) {
            return back()->with('error', 'Only the table host can start the hand.');
        }

        $occupiedSeats = $table->occupiedSeats()->with('seatHand')->get();
        if ($occupiedSeats->count() < 2) {
            return back()->with('error', 'Need at least 2 players to start a hand.');
        }

        try {
            DB::transaction(function () use ($table, $occupiedSeats, &$hand) {
                $hand = $this->handService->initializeHand($table, $occupiedSeats);

                $round = $hand->rounds()->create([
                    'type' => 'preflop',
                    'is_complete' => false,
                ]);

                // Refresh the occupied seats to get the newly created seatHands
                $occupiedSeats->each(function ($seat) {
                    $seat->refresh();
                    $seat->load('seatHand');
                });

                // Bet for SB and BB
                $SB = Seat::with('player')->find($hand->small_blind_id);
                $BB = Seat::with('player')->find($hand->big_blind_id);
                $SB_A = $this->actionService->betSBandBB($hand, $round, $SB, 1); #TODO make $amount depend on table settings - add new columns
                $BB_A = $this->actionService->betSBandBB($hand, $round, $BB, 2); #TODO make sure this is only done when player balance > 2
    
                DB::afterCommit(function () use ($table) {
                    broadcast(new TableStateChanged($table->id, $this->pokerStateService->getTableState($table)));
                });
            });
    
            return response()->json([
                'message' => 'Hand started successfully.',
                'handId'  => $hand->id,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to start hand: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getState(Table $table)
    {
        return $this->pokerStateService->getTableState($table);
    }
}
