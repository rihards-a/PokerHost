<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Hand;
use App\Models\SeatHand;
use App\Models\Table;

class HandService
{
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
        $hand = new Hand();
        $hand->table_id = $table->id;
        $hand->community_cards = ["Ah", "Kd", "Qs", "Jc", "9h"]; #TODO create a deck and shuffle - make sure users cannot see this or deal it incrementally
        $hand->dealer_id = $occupiedSeats->first()->id; #TODO maybe use a dealer offset and then skip 'offset % count'
        $hand->small_blind_id = $occupiedSeats->find($hand->dealer_id)->nextActive->id;
        $hand->big_blind_id = $occupiedSeats->find($hand->small_blind_id)->nextActive->id; #TODO edge case if only 2 players
        $hand->save();

        // Deal cards to each player
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

    public function finalizeHand(Hand $hand) 
    {
        // TODO maybe calculate winners, maybe this is unnecessary in this service
        // maybe deal out all the cards and then calculate winners if all-in situation, maybe in different service...
    }
}
