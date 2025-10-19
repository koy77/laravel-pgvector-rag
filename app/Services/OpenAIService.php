<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl;
    private string $embeddingModel;
    private string $chatModel;
    private int $embeddingDimensions;

    // RAG Prompt Templates
    private const SYSTEM_PROMPT = "You are a helpful AI assistant that answers questions based on the provided document context. 
Use only the information from the provided documents to answer questions. If the information is not available in the documents, 
say so clearly. Always cite your sources by referencing the document excerpts provided. Look carefully through all the provided 
context to find relevant information, even if it's mentioned briefly.";

    private const USER_PROMPT_TEMPLATE = "Context from relevant documents:
{context}

Chat History:
{chat_history}

User Question: {question}

Please provide a comprehensive answer based on the context above. Look through all the provided document excerpts carefully 
to find any relevant information. Pay special attention to:
- Technology names, frameworks, and tools mentioned
- Skills, experience, and expertise areas
- Project descriptions and work experience
- Any specific details that relate to the question

Include specific references to the document excerpts that support your answer. If you find relevant information, explain it 
clearly and cite the source document. If the information is mentioned in the context, please provide it even if it's brief.";

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->baseUrl = 'https://api.openai.com/v1';
        $this->embeddingModel = config('openai.embedding_model', 'text-embedding-3-small');
        $this->chatModel = config('openai.chat_model', 'gpt-3.5-turbo');
        $this->embeddingDimensions = config('openai.embedding_dimensions', 1536);

        if (!$this->apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }

    /**
     * Generate embedding for given text
     */
    public function generateEmbedding(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/embeddings', [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            return $data['data'][0]['embedding'];

        } catch (\Exception $e) {
            Log::error('OpenAI embedding generation failed', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100) . '...'
            ]);
            throw new \Exception('Failed to generate embedding: ' . $e->getMessage());
        }
    }

    /**
     * Generate conversational response using RAG
     */
    public function chatWithContext(
        string $question,
        array $contextDocuments,
        array $chatHistory = [],
        float $temperature = 0.0
    ): array {
        try {
            // Build context from retrieved documents
            $context = $this->buildContextFromDocuments($contextDocuments);
            
            // Build chat history string
            $chatHistoryString = $this->buildChatHistoryString($chatHistory);
            
            // Build user prompt
            $userPrompt = str_replace([
                '{context}',
                '{chat_history}',
                '{question}'
            ], [
                $context,
                $chatHistoryString,
                $question
            ], self::USER_PROMPT_TEMPLATE);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->chatModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => $temperature,
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            $answer = $data['choices'][0]['message']['content'];

            return [
                'answer' => $answer,
                'sources' => $this->formatSources($contextDocuments),
                'usage' => $data['usage'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI chat completion failed', [
                'error' => $e->getMessage(),
                'question' => $question,
                'context_count' => count($contextDocuments)
            ]);
            throw new \Exception('Failed to generate chat response: ' . $e->getMessage());
        }
    }

    /**
     * Build context string from retrieved documents
     */
    private function buildContextFromDocuments(array $documents): string
    {
        $contextParts = [];
        
        foreach ($documents as $index => $document) {
            $excerpt = $this->truncateText($document['content'], 1500); // Increased context size
            $similarity = $document['similarity'] ?? (1 - $document['distance']);
            $contextParts[] = "Document " . ($index + 1) . " (ID: {$document['id']}, Similarity: " . 
                            number_format($similarity * 100, 1) . "%):\n{$excerpt}\n";
        }
        
        return implode("\n---\n", $contextParts);
    }

    /**
     * Build chat history string
     */
    private function buildChatHistoryString(array $chatHistory): string
    {
        if (empty($chatHistory)) {
            return "No previous conversation.";
        }

        $historyParts = [];
        foreach ($chatHistory as $message) {
            $role = $message['role'] === 'user' ? 'User' : 'Assistant';
            $historyParts[] = "{$role}: {$message['content']}";
        }
        
        return implode("\n", $historyParts);
    }

    /**
     * Format sources for response
     */
    private function formatSources(array $documents): array
    {
        $sources = [];
        
        foreach ($documents as $document) {
            $similarity = $document['similarity'] ?? (1 - $document['distance']);
            $sources[] = [
                'id' => $document['id'],
                'filename' => $document['filename'],
                'score' => round($similarity * 100, 1),
                'excerpt' => $this->truncateText($document['content'], 200)
            ];
        }
        
        return $sources;
    }

    /**
     * Truncate text to specified length
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Get embedding dimensions
     */
    public function getEmbeddingDimensions(): int
    {
        return $this->embeddingDimensions;
    }
}
