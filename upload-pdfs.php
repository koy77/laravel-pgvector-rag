<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📄 Uploading PDFs from docs folder\n";
echo "==================================\n\n";

try {
    $openAIService = new \App\Services\OpenAIService();
    $chunkingService = new \App\Services\TextChunkingService();
    
    $testFiles = [
        'NEJMra1816604.pdf',
        'coffee.pdf'
    ];
    
    foreach ($testFiles as $filename) {
        echo "Processing: {$filename}\n";
        echo str_repeat('-', 50) . "\n";
        
        $filePath = "/var/www/html/storage/app/test-docs/{$filename}";
        
        if (!file_exists($filePath)) {
            echo "❌ File not found: {$filePath}\n\n";
            continue;
        }
        
        // Extract text from PDF
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $content = $pdf->getText();
        
        if (empty(trim($content))) {
            echo "❌ Could not extract text from PDF\n\n";
            continue;
        }
        
        echo "✅ Text extracted successfully\n";
        echo "📊 Content length: " . strlen($content) . " characters\n";
        echo "📊 Estimated tokens: " . $chunkingService->estimateTokens($content) . "\n";
        
        // Check if chunking is needed
        if ($chunkingService->needsChunking($content)) {
            echo "🧩 Document needs chunking\n";
            
            // Create document record
            $document = \App\Models\Document::create([
                'filename' => $filename,
                'content' => $content,
                'embedding' => null,
            ]);
            
            echo "✅ Document record created (ID: {$document->id})\n";
            
            // Split into chunks
            $chunks = $chunkingService->chunkText($content);
            echo "📦 Created " . count($chunks) . " chunks\n";
            
            $processedChunks = 0;
            foreach ($chunks as $chunkData) {
                try {
                    // Generate embedding for chunk
                    $embedding = $openAIService->generateEmbedding($chunkData['content']);
                    
                    // Save chunk using raw SQL for vector support
                    $vectorString = '[' . implode(',', $embedding) . ']';
                    \DB::insert('
                        INSERT INTO document_chunks (document_id, chunk_index, content, embedding, token_count, created_at, updated_at)
                        VALUES (?, ?, ?, ?::vector, ?, NOW(), NOW())
                    ', [
                        $document->id,
                        $chunkData['index'],
                        $chunkData['content'],
                        $vectorString,
                        $chunkData['token_count']
                    ]);
                    
                    $processedChunks++;
                    echo "  ✅ Chunk {$chunkData['index']} processed ({$chunkData['token_count']} tokens)\n";
                    
                } catch (\Exception $e) {
                    echo "  ❌ Error processing chunk {$chunkData['index']}: " . $e->getMessage() . "\n";
                }
            }
            
            echo "🎉 Successfully processed {$processedChunks}/" . count($chunks) . " chunks\n";
            
        } else {
            echo "📄 Document is small enough for direct processing\n";
            
            try {
                // Generate embedding
                $embedding = $openAIService->generateEmbedding($content);
                
                // Save document using raw SQL for vector support
                $vectorString = '[' . implode(',', $embedding) . ']';
                $documentId = \DB::insertGetId('
                    INSERT INTO documents (filename, content, embedding, created_at, updated_at)
                    VALUES (?, ?, ?::vector, NOW(), NOW())
                ', [$filename, $content, $vectorString]);
                
                $document = \App\Models\Document::find($documentId);
                
                echo "✅ Document processed successfully (ID: {$document->id})\n";
                
            } catch (\Exception $e) {
                echo "❌ Error processing document: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    echo "🎉 All documents processed!\n";
    
    // Show summary
    $totalDocs = \App\Models\Document::count();
    $totalChunks = \App\Models\DocumentChunk::count();
    
    echo "\n📊 Summary:\n";
    echo "   Documents: {$totalDocs}\n";
    echo "   Chunks: {$totalChunks}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
