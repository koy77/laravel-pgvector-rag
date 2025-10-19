@extends('layouts.app')

@section('title', 'Search Documents')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-search me-2"></i>Search Documents
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('search.search') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="query" class="form-label">Search Query</label>
                        <textarea 
                            class="form-control" 
                            id="query" 
                            name="query" 
                            rows="3" 
                            placeholder="Enter your search query here..."
                            required
                        >{{ old('query', $query ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </form>
            </div>
        </div>

        @if(isset($results) && $results)
            <div class="mt-4">
                <h5>Search Results for: "{{ $query }}"</h5>
                
                @if($documents->count() > 0)
                    @foreach($documents as $document)
                        <div class="search-result">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1">
                                    <i class="fas fa-file-pdf me-2 text-danger"></i>
                                    {{ $document->filename }}
                                </h6>
                                <span class="similarity-score">
                                    Similarity: {{ number_format((1 - $document->distance) * 100, 1) }}%
                                </span>
                            </div>
                            <div class="content-preview">
                                <p class="text-muted mb-0">
                                    {{ Str::limit($document->content, 500) }}
                                </p>
                            </div>
                            <small class="text-muted">
                                Uploaded: {{ $document->created_at->format('M d, Y H:i') }}
                            </small>
                        </div>
                    @endforeach
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
@endsection
