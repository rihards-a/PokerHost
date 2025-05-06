<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Hand;
use App\Models\SeatHand;
use App\Models\Table;

class HandService
{
    protected $deckService, $positionService;

    public function __construct(DeckService $deckService, PositionService $positionService)
    {
        $this->deckService = $deckService;
        $this->positionService = $positionService;
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
            'community_cards' => ["Ah", "Kd", "Qs", "Jc", "9h"], #TODO create a deck and shuffle - make sure users cannot see this or deal it incrementally
            'dealer_id' => $occupiedSeats->first()->id, #TODO maybe use a dealer offset and then skip 'offset % count'
            'small_blind_id' => $occupiedSeats->find($occupiedSeats->first()->id)->nextActive->id,
            'big_blind_id' => $occupiedSeats->find($occupiedSeats->first()->id)->nextActive->nextActive->id, #TODO edge case if only 2 players
            'is_complete' => false,
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

    public function finalizeHand(Hand $hand) 
    {
        // TODO maybe calculate winners, maybe this is unnecessary in this service
        // maybe deal out all the cards and then calculate winners if all-in situation, maybe in different service...
        $winners = [];
        $seatHands = $hand->seatHands()->where('folded', false)->get();

        return $winners;
    }
}
