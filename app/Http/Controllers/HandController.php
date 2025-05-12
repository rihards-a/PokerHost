<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Seat;
use App\Services\HandService;
use App\Services\ActionService;
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
    protected $handService, $actionService;

    public function __construct(HandService $handService, ActionService $actionService)
    {
        $this->handService = $handService;
        $this->actionService = $actionService;
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
    
                DB::afterCommit(function () use ($table, $occupiedSeats, $hand, $SB, $BB, $SB_A, $BB_A) {
                    foreach ($occupiedSeats as $seat) {
                        broadcast(new PlayerCardsDealt($table->id, $seat->id, [
                            'card1' => $seat->seatHand->first()->card1,
                            'card2' => $seat->seatHand->first()->card2,
                        ]));
                    }
                    broadcast(new HandStarted($table->id, $hand->id, [
                        'dealer'       => Seat::find($hand->dealer_id)->position,
                        'small_blind'  => $SB->position,
                        'big_blind'    => $BB->position,
                    ]));
                    
                    broadcast(new ActionTaken($table->id, $SB_A));
                    broadcast(new ActionTaken($table->id, $BB_A));
                    $nextToAct = $table->occupiedSeats->find($hand->big_blind_id)->getNextActive()->id;
                    broadcast(new PlayerTurnChanged($table->id, $nextToAct));
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
}
