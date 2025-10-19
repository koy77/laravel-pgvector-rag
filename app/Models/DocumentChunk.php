<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'token_count',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    protected $attributes = [
        'embedding' => null,
    ];

    /**
     * Get the document that owns this chunk
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get chunks similar to the given embedding using PGVector
     */
    public static function findSimilarRaw(array $queryEmbedding, int $limit = 10): array
    {
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';
        
        // Use cosine similarity (1 - cosine distance) for better results
        $results = \DB::select("
            SELECT dc.id, dc.document_id, dc.chunk_index, dc.content, 
                   d.filename,
                   1 - (dc.embedding <=> ?::vector) as similarity,
                   dc.embedding <-> ?::vector as distance,
                   dc.created_at, dc.updated_at
            FROM document_chunks dc
            JOIN documents d ON dc.document_id = d.id
            ORDER BY dc.embedding <=> ?::vector 
            LIMIT ?
        ", [$vectorString, $vectorString, $vectorString, $limit]);
        
        return array_map(function($row) {
            return [
                'id' => $row->id,
                'document_id' => $row->document_id,
                'chunk_index' => $row->chunk_index,
                'content' => $row->content,
                'filename' => $row->filename,
                'distance' => (float) $row->distance,
                'similarity' => (float) $row->similarity,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at
            ];
        }, $results);
    }
}