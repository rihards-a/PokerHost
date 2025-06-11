<?php

namespace App\Services;

class DeckService 
{
    /**
     * Create a shuffled deck of cards
     */
    public function createDeck() 
    {
        $suits = ['h', 'd', 'c', 's'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $deck = [];
        
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $deck[] = $rank . $suit;
            }
        }
        
        // Shuffle the deck
        shuffle($deck);
        
        return $deck;
    }
    
    /**
     * Deal cards from the deck
     * Returns array of dealt cards and remaining deck
     */
    public function dealCards($deck, $count) 
    {
        $dealtCards = array_splice($deck, 0, $count);
        return [
            'dealt_cards' => $dealtCards,
            'remaining_deck' => $deck
        ];
    }
    
    /**
     * Evaluate a poker hand
     * @param array $playerCards Array of player cards
     * @param array $communityCards Array of community cards
     * @return array [rank => hand rank, description => hand description]
     */
    public function evaluateHand(array $playerCards, array $communityCards)
    {
        // Combine player and community cards
        $allCards = array_merge($playerCards, $communityCards);
        
        // For now, return a basic evaluation
        // In a real implementation, you would check for pairs, straights, flushes, etc.
        // and return a numeric rank (higher is better) and a description
        
        // This is a simplified implementation
        return [
            'rank' => rand(1, 99), // Random rank for now
            'description' => 'A hand evaluation' // Description of the hand
        ];
    }
}
