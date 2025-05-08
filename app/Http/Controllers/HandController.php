<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Services\HandService;
use App\Events\HandStarted;
use App\Events\PlayerTurnChanged;
use App\Events\PlayerCardsDealt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HandController extends Controller
{
    protected $handService;

    public function __construct(HandService $handService)
    {
        $this->handService = $handService;
    }

    /**
     * Start a new hand at the table.
     */
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

                // Refresh the occupied seats to get the newly created seatHands
                $occupiedSeats->each(function ($seat) {
                    $seat->refresh();
                    $seat->load('seatHand');
                });
    
                DB::afterCommit(function () use ($table, $occupiedSeats, $hand) {
                    foreach ($occupiedSeats as $seat) {
                        broadcast(new PlayerCardsDealt($table->id, $seat->id, [
                            'card1' => $seat->seatHand->first()->card1,
                            'card2' => $seat->seatHand->first()->card2,
                        ]));
                    }
                    $nextToAct = $table->occupiedSeats->find($hand->big_blind_id)->getNextActive()->id;
                    broadcast(new HandStarted($table->id, $hand->id, [
                        'dealer'       => $hand->dealer_seat_id,
                        'small_blind'  => $hand->small_blind_id,
                        'big_blind'    => $hand->big_blind_id,
                        'next_to_act'  => $nextToAct,
                    ]));
    
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
