<?php

namespace App\Service;

class CommentModerationService
{
    // A basic list of profane/toxic words for demonstration.
    private const PROFANITY_LIST = [
        'fuck', 'shit', 'bitch', 'asshole', 'cunt', 'dick', 'pussy', 'slut', 'whore',
        'bastard', 'motherfucker', 'crap', 'bullshit', 'damn', 'idiot',
        'connard', 'salope', 'putain', 'merde', 'enculé', 'fdp'
    ];

    // Common spam phrases
    private const SPAM_PHRASES = [
        'buy now', 'click here', 'make money fast', 'earn money', 'viagra', 'casino',
        'crypto', 'bitcoin', 'investment', 'free trial', 'winner', 'lottery',
        'buy cheap stuff'
    ];

    // Harsh artistic criticism or toxic patterns
    private const TOXIC_PATTERNS = [
        '/est nulle/iu',
        '/n\'y conna[îi]t rien/iu',
        '/quel gâchis/iu',
        '/horrible/iu',
        '/artwork is terrible/iu',
        '/artist knows nothing/iu',
        '/you are an idiot/iu'
    ];

    /**
     * Moderates the given content.
     * 
     * @param string $content The comment content to check
     * @return array{is_clean: bool, reason: ?string, suggested_status: string}
     */
    public function moderate(string $content): array
    {
        $lowercaseContent = strtolower($content);

        // 1. Check for Profanity / Toxic Language
        foreach (self::PROFANITY_LIST as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $lowercaseContent)) {
                return [
                    'is_clean' => false,
                    'reason' => 'Offensive language detected.',
                    'suggested_status' => 'rejected'
                ];
            }
        }

        // 2. Check for Toxic Patterns (Context-aware insults)
        foreach (self::TOXIC_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return [
                    'is_clean' => false,
                    'reason' => 'Insulting or excessively harsh criticism detected.',
                    'suggested_status' => 'rejected'
                ];
            }
        }

        // 3. Check for Repetitive Words (e.g., "spam spam spam")
        if ($this->hasRepetitiveWords($lowercaseContent)) {
            return [
                'is_clean' => false,
                'reason' => 'Repetitive spam detected.',
                'suggested_status' => 'rejected'
            ];
        }

        // 4. Check for Spam Phrases
        foreach (self::SPAM_PHRASES as $phrase) {
            if (str_contains($lowercaseContent, $phrase)) {
                return [
                    'is_clean' => false,
                    'reason' => 'Spam-like phrase detected.',
                    'suggested_status' => 'rejected'
                ];
            }
        }

        // 5. Check for Suspicious Links
        $urlCount = preg_match_all('/https?:\/\//i', $lowercaseContent);
        if ($urlCount > 1) {
             return [
                 'is_clean' => false,
                 'reason' => 'Multiple links detected (potential spam).',
                 'suggested_status' => 'pending'
             ];
        }

        // 6. Check for Repetitive Characters (e.g., 'aaaaa', '!!!!')
        if (preg_match('/(.)\1{4,}/', $content)) {
            return [
                'is_clean' => false,
                'reason' => 'Excessive repetitive formatting detected.',
                'suggested_status' => 'pending'
            ];
        }

        // 7. Check for ALL CAPS (excessive shouting)
        $alphaOnly = preg_replace('/[^a-zA-Z]/', '', $content);
        if (strlen($alphaOnly) > 10) {
            $upperCount = preg_match_all('/[A-Z]/', $alphaOnly);
            if (($upperCount / strlen($alphaOnly)) > 0.8) {
                return [
                    'is_clean' => false,
                    'reason' => 'Excessive capitalization detected.',
                    'suggested_status' => 'pending'
                ];
            }
        }

        return [
            'is_clean' => true,
            'reason' => null,
            'suggested_status' => 'approved'
        ];
    }

    /**
     * Detects if a word is repeated 3 or more times consecutively or frequently.
     */
    private function hasRepetitiveWords(string $text): bool
    {
        // Split into words, ignoring punctuation
        $words = preg_split('/[\s,;.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($words) < 3) {
            return false;
        }

        // Count occurrences
        $counts = array_count_values($words);
        foreach ($counts as $word => $count) {
            // If any word (longer than 2 chars) appears 3+ times in a short message
            if (strlen($word) > 2 && $count >= 3) {
                return true;
            }
        }

        return false;
    }
}
