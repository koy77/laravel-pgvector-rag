<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index()
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:1000',
            'use_ai' => 'boolean',
            'chat_history' => 'array',
        ]);

        try {
            $query = $request->input('query');
            $useAI = $request->boolean('use_ai', true); // Default to AI-powered search
            $chatHistory = $request->input('chat_history', []);

            // Generate embedding for the search query
            $queryEmbedding = $this->openAIService->generateEmbedding($query);

            // Find similar documents using raw SQL for better performance
            $similarDocuments = Document::findSimilarRaw($queryEmbedding, 5);

            if ($useAI && !empty($similarDocuments)) {
                // RAG-based conversational AI response
                $aiResponse = $this->openAIService->chatWithContext(
                    $query,
                    $similarDocuments,
                    $chatHistory,
                    0.0 // Deterministic temperature
                );

                return view('search', [
                    'query' => $query,
                    'documents' => collect($similarDocuments),
                    'ai_response' => $aiResponse,
                    'use_ai' => true,
                    'results' => true
                ]);
            } else {
                // Fallback to traditional similarity search
                return view('search', [
                    'query' => $query,
                    'documents' => collect($similarDocuments),
                    'use_ai' => false,
                    'results' => true
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to simple search without AI
            try {
                $query = $request->input('query');
                $queryEmbedding = $this->openAIService->generateEmbedding($query);
                $similarDocuments = Document::findSimilarRaw($queryEmbedding, 5);

                return view('search', [
                    'query' => $query,
                    'documents' => collect($similarDocuments),
                    'use_ai' => false,
                    'results' => true,
                    'warning' => 'AI search temporarily unavailable. Showing similarity results only.'
                ]);
            } catch (\Exception $fallbackError) {
                return back()->with('error', 'Search failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * API endpoint for AJAX search requests
     */
    public function searchApi(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:1000',
            'use_ai' => 'boolean',
        ]);

        try {
            $query = $request->input('query');
            $useAI = $request->boolean('use_ai', true);

            // Generate embedding for the search query
            $queryEmbedding = $this->openAIService->generateEmbedding($query);

            // Find similar documents
            $similarDocuments = Document::findSimilarRaw($queryEmbedding, 5);

            if ($useAI && !empty($similarDocuments)) {
                // RAG-based conversational AI response
                $aiResponse = $this->openAIService->chatWithContext(
                    $query,
                    $similarDocuments,
                    [],
                    0.0
                );

                return response()->json([
                    'success' => true,
                    'query' => $query,
                    'documents' => $similarDocuments,
                    'ai_response' => $aiResponse,
                    'use_ai' => true
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'query' => $query,
                    'documents' => $similarDocuments,
                    'use_ai' => false
                ]);
            }

        } catch (\Exception $e) {
            Log::error('API search failed', [
                'query' => $request->input('query'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
