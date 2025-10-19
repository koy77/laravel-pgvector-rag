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
        $this->maxTokens = config('openai.max_context_length', 4000); // Safe margin
        $this->overlapTokens = 200; // Overlap between chunks for context
        $this->maxChunkSize = $this->maxTokens - $this->overlapTokens;
    }

    /**
     * Split text into chunks for embedding generation
     */
    public function chunkText(string $text): array
    {
        // First, try to split by paragraphs
        $paragraphs = $this->splitByParagraphs($text);
        
        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;
        $chunkIndex = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraphTokens = $this->estimateTokens($paragraph);
            
            // If single paragraph is too large, split it by sentences
            if ($paragraphTokens > $this->maxChunkSize) {
                $sentences = $this->splitBySentences($paragraph);
                
                foreach ($sentences as $sentence) {
                    $sentenceTokens = $this->estimateTokens($sentence);
                    
                    if ($currentTokens + $sentenceTokens > $this->maxChunkSize && !empty($currentChunk)) {
                        $chunks[] = [
                            'index' => $chunkIndex++,
                            'content' => trim($currentChunk),
                            'token_count' => $currentTokens
                        ];
                        
                        // Start new chunk with overlap
                        $currentChunk = $this->getOverlapText($currentChunk) . $sentence;
                        $currentTokens = $this->estimateTokens($currentChunk);
                    } else {
                        $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                        $currentTokens += $sentenceTokens;
                    }
                }
            } else {
                // Check if adding this paragraph would exceed limit
                if ($currentTokens + $paragraphTokens > $this->maxChunkSize && !empty($currentChunk)) {
                    $chunks[] = [
                        'index' => $chunkIndex++,
                        'content' => trim($currentChunk),
                        'token_count' => $currentTokens
                    ];
                    
                    // Start new chunk with overlap
                    $currentChunk = $this->getOverlapText($currentChunk) . $paragraph;
                    $currentTokens = $this->estimateTokens($currentChunk);
                } else {
                    $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
                    $currentTokens += $paragraphTokens;
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
     * Estimate token count (rough approximation: 1 token ≈ 4 characters)
     */
    public function estimateTokens(string $text): int
    {
        // More accurate estimation: count words and add some margin
        $wordCount = str_word_count($text);
        $charCount = strlen($text);
        
        // Rough estimation: 1 token ≈ 0.75 words or 4 characters
        $tokenEstimate = max($wordCount * 1.33, $charCount / 4);
        
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
