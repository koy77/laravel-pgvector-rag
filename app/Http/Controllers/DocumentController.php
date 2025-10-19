<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\OpenAIService;
use App\Services\TextChunkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    private OpenAIService $openAIService;
    private TextChunkingService $chunkingService;

    public function __construct(OpenAIService $openAIService, TextChunkingService $chunkingService)
    {
        $this->openAIService = $openAIService;
        $this->chunkingService = $chunkingService;
    }

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

            // Check if content needs chunking
            if ($this->chunkingService->needsChunking($content)) {
                return $this->processLargeDocument($filename, $content, $path);
            } else {
                return $this->processSmallDocument($filename, $content, $path);
            }

        } catch (\Exception $e) {
            // Clean up temporary file if it exists
            if (isset($path)) {
                Storage::delete($path);
            }
            
            return back()->with('error', 'Error processing PDF: ' . $e->getMessage());
        }
    }

    /**
     * Process small document (no chunking needed)
     */
    private function processSmallDocument(string $filename, string $content, string $path)
    {
        try {
            // Generate embedding using OpenAI
            $embedding = $this->openAIService->generateEmbedding($content);

            // Save document to database
            Document::create([
                'filename' => $filename,
                'content' => $content,
                'embedding' => $embedding,
            ]);

            // Clean up temporary file
            Storage::delete($path);

            return back()->with('success', "PDF '{$filename}' processed successfully! You can now search for similar content.");

        } catch (\Exception $e) {
            Storage::delete($path);
            return back()->with('error', 'Error processing PDF: ' . $e->getMessage());
        }
    }

    /**
     * Process large document (chunking required)
     */
    private function processLargeDocument(string $filename, string $content, string $path)
    {
        try {
            // Create document record without embedding
            $document = Document::create([
                'filename' => $filename,
                'content' => $content,
                'embedding' => null, // No embedding for the full document
            ]);

            // Split content into chunks
            $chunks = $this->chunkingService->chunkText($content);
            
            $processedChunks = 0;
            $totalChunks = count($chunks);

            // Process each chunk
            foreach ($chunks as $chunkData) {
                try {
                    // Generate embedding for this chunk
                    $embedding = $this->openAIService->generateEmbedding($chunkData['content']);

                    // Save chunk to database
                    DocumentChunk::create([
                        'document_id' => $document->id,
                        'chunk_index' => $chunkData['index'],
                        'content' => $chunkData['content'],
                        'embedding' => $embedding,
                        'token_count' => $chunkData['token_count'],
                    ]);

                    $processedChunks++;

                } catch (\Exception $e) {
                    // Log chunk processing error but continue with other chunks
                    \Log::error("Error processing chunk {$chunkData['index']} for document {$filename}: " . $e->getMessage());
                }
            }

            // Clean up temporary file
            Storage::delete($path);

            if ($processedChunks > 0) {
                return back()->with('success', 
                    "Large PDF '{$filename}' processed successfully! " .
                    "Document was split into {$processedChunks} chunks for better search results. " .
                    "You can now search for similar content."
                );
            } else {
                // Delete the document if no chunks were processed
                $document->delete();
                return back()->with('error', 'Failed to process any chunks from the PDF. Please try again.');
            }

        } catch (\Exception $e) {
            Storage::delete($path);
            return back()->with('error', 'Error processing large PDF: ' . $e->getMessage());
        }
    }

}
