<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use OpenAI\Laravel\Facades\OpenAI;

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
            $fullPath = storage_path('app/' . $path);

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
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response->data[0]->embedding;
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate embedding: ' . $e->getMessage());
        }
    }
}
