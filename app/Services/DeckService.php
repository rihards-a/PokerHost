<?php

namespace App\Services;

#TODO: currently not in use!!!
class DeckService
{
    /**
     * Create a shuffled deck of cards
     */
    public function createDeck()
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        
        $deck = [];
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $deck[] = [
                    'suit' => $suit,
                    'rank' => $rank
                ];
            }
        }
        
        // Shuffle the deck
        shuffle($deck);
        
        return $deck;
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
        
        // Sort cards by rank (for easier evaluation)
        usort($allCards, function($a, $b) {
            $ranks = ['2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, 
                     '8' => 8, '9' => 9, '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14];
            
            return $ranks[$a['rank']] - $ranks[$b['rank']];
        });
        
        // For now, return a basic evaluation
        // In a real implementation, you would check for pairs, straights, flushes, etc.
        // and return a numeric rank (higher is better) and a description
        
        // This is a simplified implementation
        return [
            'rank' => rand(1, 9), // Random rank for now (1-9)
            'description' => 'A hand evaluation' // Description of the hand
        ];
    }
}
