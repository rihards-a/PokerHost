<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Services\HandService;
use App\Events\HandStarted;
use App\Events\PlayerTurnChanged;
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

        $occupiedSeats = $table->occupiedSeats;
        if ($occupiedSeats->count() < 2) {
            return back()->with('error', 'Need at least 2 players to start a hand.');
        }

        try {
            DB::transaction(function () use ($table, $occupiedSeats, &$hand) {
                $hand = $this->handService->initializeHand($table, $occupiedSeats);
    
                DB::afterCommit(function () use ($table, $hand) {
                    $nextToAct = $table->occupiedSeats->find($hand->big_blind_seat_id)->nextActive()->id;
                    broadcast(new HandStarted($table->id, $hand->id, [
                        'dealer'       => $hand->dealer_seat_id,
                        'small_blind'  => $hand->small_blind_seat_id,
                        'big_blind'    => $hand->big_blind_seat_id,
                        'next_to_act'  => $nextToAct,
                    ]));
    
                    broadcast(new PlayerTurnChanged($table->id, $nextToAct));
                });
            });
    
            return redirect()->route('tables.hand', $table->id)
                ->with('success', 'Hand started successfully!');
        } catch (\Throwable $e) {     # Throwable is within the global namespace
            return back()->with('error', 'Failed to start hand: ' . $e->getMessage());
        }
    }
}
