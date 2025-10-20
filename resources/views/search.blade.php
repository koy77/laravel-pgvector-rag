@extends('layouts.app')

@section('title', 'Search Documents')

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-search me-2"></i>AI-Powered Document Search
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('search.search') }}" id="searchForm">
                    @csrf
                    <div class="mb-3">
                        <label for="query" class="form-label">Search Query</label>
                        <textarea 
                            class="form-control" 
                            id="query" 
                            name="query" 
                            rows="3" 
                            placeholder="Ask a question about your documents..."
                            required
                        >{{ old('query', $query ?? '') }}</textarea>
                    </div>
                    
                    <!-- Prebuilt Search Queries -->
                    <div class="mb-3">
                        <label class="form-label">Suggested Queries:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuery('from where coffee production in Uganda comes from')">
                                Coffee production in Uganda
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuery('how to grow coffee')">
                                How to grow coffee
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuery('who produce coffee')">
                                Who produce coffee
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuery('how much Vietnam produced In the 2024/25 season')">
                                Vietnam coffee production 2024/25
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="use_ai" name="use_ai" value="1" 
                                   {{ (isset($use_ai) && $use_ai) || !isset($use_ai) ? 'checked' : '' }}>
                            <label class="form-check-label" for="use_ai">
                                <i class="fas fa-robot me-1"></i>Use AI-powered conversational search
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="searchBtn">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </form>
            </div>
        </div>

        @if(isset($warning))
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ $warning }}
            </div>
        @endif

        @if(isset($results) && $results)
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Search Results for: "{{ $query }}"</h5>
                    @if(isset($use_ai) && $use_ai)
                        <span class="badge bg-success">
                            <i class="fas fa-robot me-1"></i>AI-Powered
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            <i class="fas fa-search me-1"></i>Similarity Search
                        </span>
                    @endif
                </div>

                @if(isset($ai_response) && $ai_response)
                    <!-- AI Response Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-robot me-2"></i>AI Assistant Response
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="ai-response">
                                {!! nl2br(e($ai_response['answer'])) !!}
                            </div>
                            
                            @if(isset($ai_response['sources']) && count($ai_response['sources']) > 0)
                                <hr>
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-quote-left me-2"></i>Sources:
                                </h6>
                                <div class="sources-list">
                                    @foreach($ai_response['sources'] as $source)
                                        <div class="source-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-file-pdf me-2 text-danger"></i>
                                                    {{ $source['filename'] }}
                                                </h6>
                                                <span class="badge bg-info">
                                                    {{ number_format($source['score'], 1) }}% match
                                                </span>
                                            </div>
                                            <p class="text-muted mb-0 small">
                                                {{ $source['excerpt'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Traditional Search Results -->
                @if($documents->count() > 0)
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>Similar Documents
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach($documents as $document)
                                <div class="search-result">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1">
                                            <i class="fas fa-file-pdf me-2 text-danger"></i>
                                            {{ $document['filename'] }}
                                            @if(isset($document['type']) && $document['type'] === 'chunk')
                                                <small class="text-muted">(Chunk {{ $document['chunk_index'] }})</small>
                                            @endif
                                        </h6>
                                        <span class="similarity-score">
                                            Similarity: {{ number_format(($document['similarity'] ?? (1 - $document['distance'])) * 100, 1) }}%
                                        </span>
                                    </div>
                                    <div class="content-preview">
                                        <p class="text-muted mb-0">
                                            {{ Str::limit($document['content'], 500) }}
                                        </p>
                                    </div>
                                    <small class="text-muted">
                                        Uploaded: {{ \Carbon\Carbon::parse($document['created_at'])->format('M d, Y H:i') }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No documents found matching your query. Try uploading some PDFs first!
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
.ai-response {
    font-size: 1.1em;
    line-height: 1.6;
    color: #333;
}

.source-item {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff !important;
}

.similarity-score {
    background: #e3f2fd;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
    color: #1976d2;
}

.content-preview {
    max-height: 200px;
    overflow: hidden;
    position: relative;
}

.content-preview::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: linear-gradient(transparent, white);
}

.search-result {
    border-left: 4px solid #007bff;
    padding-left: 15px;
    margin-bottom: 20px;
}
</style>

<script>
document.getElementById('searchForm').addEventListener('submit', function() {
    const btn = document.getElementById('searchBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Searching...';
    btn.disabled = true;
});

function setQuery(query) {
    document.getElementById('query').value = query;
}
</script>
@endsection
