<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use App\Models\Hand;
use App\Models\SeatHand;
use App\Models\Table;

class HandService extends ServiceProvider
{
    /*
    * Initialize a new hand for the table.
    *
    * @param Table $table
    * @param Collection $occupiedSeats
    * @return Hand
    */
    public function initializeHand(Table $table, Collection $occupiedSeats) {
        $hand = new Hand();
        $hand->table_id = $table->id;
        $hand->community_cards = ["Ah", "Kd", "Qs", "Jc", "9h"]; #TODO create a deck and shuffle
        $hand->dealer_id = $occupiedSeats->first()->id; #TODO maybe use a dealer offset and then skip 'offset % count'
        $hand->small_blind_id = $occupiedSeats->find($hand->dealer_id)->nextActive->id;
        $hand->big_blind_id = $occupiedSeats->find($hand->small_blind_id)->nextActive->id; #TODO edge case if only 2 players
        $hand->save();

        /* TODO
        // Create deck and shuffle
        $deck = $this->createShuffledDeck();
        */

        foreach ($occupiedSeats as $seat) {
            $seatHand = new SeatHand();
            $seatHand->hand_id = $hand->id;
            $seatHand->seat_id = $seat->id;
            $seatHand->card1 = "As"; #TODO create a deck and shuffle
            $seatHand->card2 = "Ad"; #TODO create a deck and shuffle
            $seatHand->save();
        }

        return $hand;
    }
}
