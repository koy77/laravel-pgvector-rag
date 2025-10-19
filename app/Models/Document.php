<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    protected $attributes = [
        'embedding' => null,
    ];

    /**
     * Get the chunks for this document
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    /**
     * Get documents similar to the given embedding using PGVector
     */
    public static function findSimilar(array $queryEmbedding, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        // Convert array to PostgreSQL vector format
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';
        
        return static::selectRaw('*, embedding <-> ?::vector as distance', [$vectorString])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Get documents similar to the given embedding with raw SQL for better performance
     */
    public static function findSimilarRaw(array $queryEmbedding, int $limit = 5): array
    {
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';
        
        // Use cosine similarity (1 - cosine distance) for better results
        $results = \DB::select("
            SELECT id, filename, content, 
                   1 - (embedding <=> ?::vector) as similarity,
                   embedding <-> ?::vector as distance,
                   created_at, updated_at
            FROM documents 
            ORDER BY embedding <=> ?::vector 
            LIMIT ?
        ", [$vectorString, $vectorString, $vectorString, $limit]);
        
        return array_map(function($row) {
            return [
                'id' => $row->id,
                'filename' => $row->filename,
                'content' => $row->content,
                'distance' => (float) $row->distance,
                'similarity' => (float) $row->similarity,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at
            ];
        }, $results);
    }
}
