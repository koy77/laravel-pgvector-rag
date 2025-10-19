<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create table with standard Laravel fields first
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('chunk_index')->comment('Index of chunk within the document');
            $table->text('content')->comment('Text content of this chunk');
            $table->integer('token_count')->comment('Number of tokens in this chunk');
            $table->timestamps();
            
            $table->index(['document_id', 'chunk_index']);
        });
        
        // Add vector column using raw SQL
        \DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(1536)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};