<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get documents similar to the given embedding
     */
    public static function findSimilar($queryEmbedding, $limit = 5)
    {
        return static::selectRaw('*, embedding <-> ? as distance', [json_encode($queryEmbedding)])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }
}
