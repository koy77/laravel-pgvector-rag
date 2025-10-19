<?php

namespace App\Services;

class TextChunkingService
{
    private int $maxTokens;
    private int $overlapTokens;
    private int $maxChunkSize;

    public function __construct()
    {
        // OpenAI text-embedding-3-small has 8192 token limit
        // Use a much smaller limit to be safe and account for token estimation inaccuracy
        $this->maxTokens = 3000; // Conservative limit
        $this->overlapTokens = 100; // Overlap between chunks for context
        $this->maxChunkSize = $this->maxTokens - $this->overlapTokens;
        
        // Ensure maxChunkSize is never more than 2500 to be extra safe
        if ($this->maxChunkSize > 2500) {
            $this->maxChunkSize = 2500;
        }
    }

    /**
     * Split text into chunks for embedding generation
     */
    public function chunkText(string $text): array
    {
        $chunks = [];
        $chunkIndex = 0;
        
        // Split by sentences first for better granularity
        $sentences = $this->splitBySentences($text);
        
        $currentChunk = '';
        $currentTokens = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokens($sentence);
            
            // If single sentence is too large, split it by words
            if ($sentenceTokens > $this->maxChunkSize) {
                // Save current chunk if it exists
                if (!empty(trim($currentChunk))) {
                    $chunks[] = [
                        'index' => $chunkIndex++,
                        'content' => trim($currentChunk),
                        'token_count' => $currentTokens
                    ];
                    $currentChunk = '';
                    $currentTokens = 0;
                }
                
                // Split large sentence by words
                $words = explode(' ', $sentence);
                $currentWordChunk = '';
                $currentWordTokens = 0;
                
                foreach ($words as $word) {
                    $wordTokens = $this->estimateTokens($word . ' ');
                    
                    if ($currentWordTokens + $wordTokens > $this->maxChunkSize && !empty($currentWordChunk)) {
                        $chunks[] = [
                            'index' => $chunkIndex++,
                            'content' => trim($currentWordChunk),
                            'token_count' => $currentWordTokens
                        ];
                        $currentWordChunk = $word . ' ';
                        $currentWordTokens = $wordTokens;
                    } else {
                        $currentWordChunk .= $word . ' ';
                        $currentWordTokens += $wordTokens;
                    }
                }
                
                // Add remaining word chunk
                if (!empty(trim($currentWordChunk))) {
                    $currentChunk = $currentWordChunk;
                    $currentTokens = $currentWordTokens;
                }
            } else {
                // Check if adding this sentence would exceed limit
                if ($currentTokens + $sentenceTokens > $this->maxChunkSize && !empty($currentChunk)) {
                    $chunks[] = [
                        'index' => $chunkIndex++,
                        'content' => trim($currentChunk),
                        'token_count' => $currentTokens
                    ];
                    
                    // Start new chunk with overlap (but ensure it doesn't exceed maxChunkSize)
                    $overlapText = $this->getOverlapText($currentChunk);
                    $newChunk = $overlapText . $sentence;
                    $newChunkTokens = $this->estimateTokens($newChunk);
                    
                    // If the new chunk with overlap is too large, start without overlap
                    if ($newChunkTokens > $this->maxChunkSize) {
                        $currentChunk = $sentence;
                        $currentTokens = $sentenceTokens;
                    } else {
                        $currentChunk = $newChunk;
                        $currentTokens = $newChunkTokens;
                    }
                } else {
                    $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                    $currentTokens += $sentenceTokens;
                }
            }
        }

        // Add the last chunk if it's not empty
        if (!empty(trim($currentChunk))) {
            $chunks[] = [
                'index' => $chunkIndex,
                'content' => trim($currentChunk),
                'token_count' => $this->estimateTokens($currentChunk)
            ];
        }

        return $chunks;
    }

    /**
     * Split text by paragraphs
     */
    private function splitByParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        return array_filter(array_map('trim', $paragraphs));
    }

    /**
     * Split text by sentences
     */
    private function splitBySentences(string $text): array
    {
        // Simple sentence splitting - can be improved with more sophisticated NLP
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        return array_filter(array_map('trim', $sentences));
    }

    /**
     * Get overlap text from the end of current chunk
     */
    private function getOverlapText(string $chunk): string
    {
        $words = explode(' ', $chunk);
        $overlapWords = array_slice($words, -($this->overlapTokens / 4)); // Rough estimate
        return implode(' ', $overlapWords) . ' ';
    }

    /**
     * Estimate token count (more conservative estimation)
     */
    public function estimateTokens(string $text): int
    {
        // More conservative estimation to avoid token limit errors
        $wordCount = str_word_count($text);
        $charCount = strlen($text);
        
        // Conservative estimation: 1 token ≈ 0.6 words or 3 characters
        // This gives us a safety margin
        $tokenEstimate = max($wordCount * 1.67, $charCount / 3);
        
        return (int) ceil($tokenEstimate);
    }

    /**
     * Check if text needs chunking
     */
    public function needsChunking(string $text): bool
    {
        return $this->estimateTokens($text) > $this->maxTokens;
    }

    /**
     * Get maximum tokens per chunk
     */
    public function getMaxTokensPerChunk(): int
    {
        return $this->maxChunkSize;
    }
}
