<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use OpenAI\Factory;

class DocumentController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        try {
            // Get the uploaded file
            $file = $request->file('pdf');
            $filename = $file->getClientOriginalName();
            
            // Store the file temporarily
            $path = $file->store('temp');
            $fullPath = Storage::path($path);

            // Debug: Check if file exists
            if (!file_exists($fullPath)) {
                throw new \Exception("Temporary file not found at: {$fullPath}");
            }

            // Extract text from PDF
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            $content = $pdf->getText();

            if (empty(trim($content))) {
                Storage::delete($path);
                return back()->with('error', 'Could not extract text from PDF. The file might be image-based or corrupted.');
            }

            // Generate embedding using OpenAI
            $embedding = $this->generateEmbedding($content);

            // Save document to database
            Document::create([
                'filename' => $filename,
                'content' => $content,
                'embedding' => $embedding,
            ]);

            // Clean up temporary file
            Storage::delete($path);

            return back()->with('success', 'PDF uploaded and processed successfully!');

        } catch (\Exception $e) {
            // Clean up temporary file if it exists
            if (isset($path)) {
                Storage::delete($path);
            }
            
            return back()->with('error', 'Error processing PDF: ' . $e->getMessage());
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
