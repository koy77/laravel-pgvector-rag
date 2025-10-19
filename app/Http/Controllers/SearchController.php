<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use OpenAI\Factory;

class SearchController extends Controller
{
    public function index()
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:1000',
        ]);

        try {
            $query = $request->input('query');
            
            // Generate embedding for the search query
            $queryEmbedding = $this->generateEmbedding($query);

            // Find similar documents
            $documents = Document::findSimilar($queryEmbedding, 5);

            return view('search', [
                'query' => $query,
                'documents' => $documents,
                'results' => true
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Search failed: ' . $e->getMessage());
        }
    }

    private function generateEmbedding($text)
    {
        try {
            $apiKey = config('openai.api_key');
            if (!$apiKey) {
                throw new \Exception('OpenAI API key not configured');
            }
            
            $client = (new Factory())->withApiKey($apiKey)->make();
            $response = $client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate embedding: ' . $e->getMessage());
        }
    }
}
