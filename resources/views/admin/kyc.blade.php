@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>KYC Verification Management</h2>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>

                <div class="card-body">
                    @if(count($pendingDocuments) === 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No pending KYC documents to review at this time.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-striped admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Document Type</th>
                                    <th>Submitted</th>
                                    <th>Document</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingDocuments as $document)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="ms-2">
                                                <h6 class="mb-0">{{ $document->user->name }}</h6>
                                                <small>{{ $document->user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $document->getDocumentTypeDisplay() }}</td>
                                    <td>{{ $document->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <a href="{{ Storage::url($document->document_path) }}" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-file-alt me-1"></i> View Document
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <form action="{{ route('admin.kyc.approve', $document->id) }}" method="POST" class="me-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check me-1"></i> Approve
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $document->id }}">
                                                <i class="fas fa-times me-1"></i> Reject
                                            </button>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal{{ $document->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $document->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="rejectModalLabel{{ $document->id }}">Reject Document</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.kyc.reject', $document->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                                                                <div class="form-text">This reason will be visible to the user.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject Document</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $pendingDocuments->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
