<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'max_retries' => env('OPENAI_MAX_RETRIES', 3),
    
    // RAG Configuration
    'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
    'embedding_dimensions' => env('OPENAI_EMBEDDING_DIM', 1536),
    
    // RAG Settings
    'similarity_threshold' => env('OPENAI_SIMILARITY_THRESHOLD', 0.7),
    'max_context_documents' => env('OPENAI_MAX_CONTEXT_DOCS', 5),
    'max_context_length' => env('OPENAI_MAX_CONTEXT_LENGTH', 4000),
];
