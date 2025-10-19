@extends('layouts.app')

@section('title', 'Upload PDF')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-upload me-2"></i>Upload PDF Document
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('documents.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="pdf" class="form-label">Select PDF File</label>
                        <input 
                            type="file" 
                            class="form-control" 
                            id="pdf" 
                            name="pdf" 
                            accept=".pdf"
                            required
                        >
                        <div class="form-text">
                            Maximum file size: 10MB. The PDF will be processed to extract text and generate embeddings for similarity search.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload & Process
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>How it works
                </h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Upload:</strong> Select a PDF file and upload it to the system.</li>
                    <li><strong>Extract:</strong> The system extracts text content from your PDF.</li>
                    <li><strong>Embed:</strong> Text is converted to vector embeddings using OpenAI's embedding model.</li>
                    <li><strong>Store:</strong> The document and its embedding are stored in the database.</li>
                    <li><strong>Search:</strong> You can now search for similar content using natural language queries.</li>
                </ol>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> Make sure your PDF contains extractable text. Image-based PDFs or scanned documents may not work properly.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
