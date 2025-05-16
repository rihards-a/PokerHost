<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Hand;
use App\Models\SeatHand;
use App\Models\Table;

class HandService
{
    protected $deckService, $positionService, $transactionService;

    public function __construct(DeckService $deckService, PositionService $positionService, TransactionService $transactionService)
    {
        $this->deckService = $deckService;
        $this->positionService = $positionService;
        $this->transactionService = $transactionService;
    }

    /*
    * Initialize a new hand for the table.
    *
    * @param Table $table
    * @param Collection $occupiedSeats
    * @return Hand
    */
    public function initializeHand(Table $table, Collection $occupiedSeats) 
    {
        /* TODO
        // Create deck and shuffle
        $deck = $this->createShuffledDeck();
        */

        // Deal cards to the table. Determine dealer, small blind, and big blind
        $hand = Hand::create([
            'table_id' => $table->id,
            'community_cards' => json_encode(["Ah", "Kd", "Qs", "Jc", "9h"]), #TODO create a deck and shuffle - make sure users cannot see this or deal it incrementally
            'dealer_id' => $occupiedSeats->first()->id, #TODO maybe use a dealer offset and then skip 'offset % count'
            'small_blind_id' => $occupiedSeats->find($occupiedSeats->first()->id)->getNextActive()->id,
            'big_blind_id' => $occupiedSeats->find($occupiedSeats->first()->id)->getNextActive()->getNextActive()->id, #TODO edge case if only 2 players
            'is_complete' => false,
            'pot_size' => 0,
        ]);

        // Deal cards to each player
        foreach ($occupiedSeats as $seat) {
            SeatHand::create([
                'status' => 'active',
                'hand_id' => $hand->id,
                'seat_id' => $seat->id,
                'card1' => 'As', #TODO create a deck and shuffle - make sure users cannot see this or deal it incrementally
                'card2' => 'Ks', #TODO create a deck and shuffle - make sure users cannot see this or deal it incrementally
            ]);
        }

        return $hand;
    }

    // Finalize the hand, determine winners and execute all the winning transactions.
    public function finalizeHand(Hand $hand) 
    {
        if (!$hand->is_complete) {
            throw new \Exception('Hand is not complete.'); // Hand gets completed by the round service! 
        }
        // TODO deal out all the cards - configure the round service to do this one by one - or rather call its methods one by one since no players are 'active' yet not 'folded'
        $winners = [];
        $seatHands = $hand->seatHands()->whereNot('status', 'folded')->with('seat.player')->get();

        $dealerSeat = $hand->table->seats()->find($hand->dealer_id);
        $totalSeats = $hand->table->seats()->count(); // Needed for wraparound

        foreach ($seatHands as $seatHand) {
            $handRank = $this->deckService->evaluateHand([$seatHand->card1, $seatHand->card2], json_decode($hand->community_cards));
            $winners[] = [
                'seat_id' => $seatHand->seat_id,
                'hand_rank' => $handRank,
                'distance_from_dealer' => ($seatHand->seat->position - $dealerSeat->position + $totalSeats) % $totalSeats,
                'seat_hand' => $seatHand, // Include the entire seatHand object
                'player' => $seatHand->seat->player, // Include the player object
            ];
        }

        $winners = collect($winners)->sortByDesc('hand_rank')->values()->all();

        // Distribute the pot to the winners
        $best_hand = $winners[0]['hand_rank'];
        $everyone = $winners;
        $winners = array_filter($winners, function ($winner) use ($best_hand) {
            return $winner['hand_rank'] === $best_hand;
        });
        $winners = collect($winners)->sortBy('distance_from_dealer')->values()->all();
        $this->transactionService->distributePot($hand->pot_size, $winners);

        // Update database status for seatHands
        $winner_ids = array_column($winners, 'seat_id');
        $losers = array_filter($everyone, function ($player) use ($winner_ids) {
            return !in_array($player['seat_id'], $winner_ids);
        });
        $losers = array_values($losers); // Re-index the array to account for missing values

        // Update seatHand status - win or bust
        foreach ($winners as $winner) {
            $winner['seat_hand']->update([
                'status' => 'won',
            ]);
        }
        foreach ($losers as $loser) {
            $loser['seat_hand']->update([
                'status' => 'busted',
            ]);
        }

        #TODO show the cards of players who won

        return $winners;
    }
}
