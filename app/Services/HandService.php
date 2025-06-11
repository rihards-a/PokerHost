<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Hand;
use App\Models\SeatHand;
use App\Models\Table;
use App\Models\Action;

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
        // Create deck and shuffle
        $deck = $this->deckService->createDeck();
        
        // Calculate dealer, small blind, big blind
        list($dealerId, $smallBlindId, $bigBlindId) = $this->calculateDealerSBBB($table, $occupiedSeats);
        
        // Deal 2 cards to each player first
        $playerCards = [];
        $playerCount = $occupiedSeats->count();
        
        // Deal 2 rounds of cards (one card to each player, then second card to each player)
        for ($round = 0; $round < 2; $round++) {
            foreach ($occupiedSeats as $index => $seat) {
                $dealResult = $this->deckService->dealCards($deck, 1);
                $deck = $dealResult['remaining_deck']; // Update deck after each deal
                
                if (!isset($playerCards[$seat->id])) {
                    $playerCards[$seat->id] = [];
                }
                $playerCards[$seat->id][] = $dealResult['dealt_cards'][0];
            }
        }
        
        // Deal community cards - FIX: Extract just the dealt cards and update deck
        $communityCardsResult = $this->deckService->dealCards($deck, 5);
        $deck = $communityCardsResult['remaining_deck']; // Update deck (though not needed after this)
        $communityCards = $communityCardsResult['dealt_cards']; // Only store the actual 5 community cards
            
        // Create the hand record
        $hand = Hand::create([
            'table_id' => $table->id,
            'community_cards' => json_encode($communityCards),
            'dealer_id' => $dealerId,
            'small_blind_id' => $smallBlindId,
            'big_blind_id' => $bigBlindId,
            'is_complete' => false,
            'pot_size' => 0,
        ]);
        
        // Create seat hand records for each player
        foreach ($occupiedSeats as $seat) {
            SeatHand::create([
                'status' => 'active',
                'hand_id' => $hand->id,
                'seat_id' => $seat->id,
                'card1' => $playerCards[$seat->id][0],
                'card2' => $playerCards[$seat->id][1],
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

        $winners_API = [];
        // Update seatHand status - win or bust
        foreach ($winners as $winner) {
            $roundIds = $hand->rounds->pluck('id');
            $won = $winner['player']->transactions()->latest()->first()->amount;
            $spent = Action::whereIn('round_id', $roundIds)
                ->where('seat_id', $winner['seat_id'])
                ->sum('amount');
            $profit = $won - $spent;
            $winners_API[] = [
                'seat_id' => $winner['seat_id'],
                'hand_rank' => $winner['hand_rank'],
                'amount' => $profit,
                'card1' => $winner['seat_hand']->card1,
                'card2' => $winner['seat_hand']->card2,
            ];
            $winner['seat_hand']->update([
                'status' => 'won',
            ]);
        }
        foreach ($losers as $loser) {
            $loser['seat_hand']->update([
                'status' => 'busted',
            ]);
            $player = $loser['player'];
            if ($player->balance < 1) {
                $player->update([
                    'active' => false,
                ]);
            }
        }

        #TODO show the cards of players who won

        return $winners_API;
    }

    protected function calculateDealerSBBB(Table $table, Collection $occupiedSeats) {
        $offset = $table->hands()->count() % $table->max_seats;
        if ($offset > 0 && count($occupiedSeats) > 0) {
            $offset = $offset % count($occupiedSeats);
            $occupiedSeats = $occupiedSeats
                ->slice($offset)                          // seats at positions [$offset .. end]
                ->concat($occupiedSeats->slice(0, $offset)) // then seats [0 .. $offset-1]
                ->values();   
        }
        $dealer = $occupiedSeats->first();
        $SB = $dealer->getNextActive();
        $BB = $SB->getNextActive();
        return [$dealer->id, $SB->id, $BB->id];
    }
}
