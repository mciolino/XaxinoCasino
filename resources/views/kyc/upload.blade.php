@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="kyc-card">
                <div class="card-header">
                    <h2>KYC Verification</h2>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <p><i class="fas fa-info-circle me-2"></i> Know Your Customer (KYC) verification is required to unlock all features of the platform.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="verification-status-card">
                                <h4>Your Verification Status</h4>
                                
                                @if(Auth::user()->kyc_status === 'verified')
                                <div class="status-badge verified">
                                    <i class="fas fa-check-circle me-2"></i> Verified
                                </div>
                                <p class="mt-3">Your account is fully verified. You have access to all platform features.</p>
                                
                                @elseif(Auth::user()->kyc_status === 'pending')
                                <div class="status-badge pending">
                                    <i class="fas fa-clock me-2"></i> Pending
                                </div>
                                <p class="mt-3">Your documents are being reviewed. This process usually takes 1-2 business days.</p>
                                
                                @else
                                <div class="status-badge unverified">
                                    <i class="fas fa-times-circle me-2"></i> Unverified
                                </div>
                                <p class="mt-3">Please upload the required documents to verify your account.</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="benefits-card">
                                <h4>Benefits of Verification</h4>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i> Higher withdrawal limits</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Access to exclusive games</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Faster payout processing</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Enhanced account security</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider my-4"></div>

                    <div class="document-upload-section">
                        <h4>Upload Verification Documents</h4>
                        <p>Please upload one of the following documents:</p>

                        <form method="POST" action="{{ route('kyc.upload') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type</label>
                                <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                    <option value="" selected disabled>Select a document type</option>
                                    <option value="id_card">ID Card</option>
                                    <option value="passport">Passport</option>
                                    <option value="driving_license">Driving License</option>
                                    <option value="utility_bill">Utility Bill (proof of address)</option>
                                </select>
                                @error('document_type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="document" class="form-label">Document File</label>
                                <input id="document" type="file" class="form-control @error('document') is-invalid @enderror" name="document" required>
                                <div class="form-text">Accepted formats: JPG, PNG, PDF. Max size: 5MB</div>
                                @error('document')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Upload Document
                                </button>
                            </div>
                        </form>
                    </div>

                    @if(count($kycDocuments) > 0)
                    <div class="section-divider my-4"></div>

                    <div class="uploaded-documents-section">
                        <h4>Uploaded Documents</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Uploaded Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kycDocuments as $document)
                                    <tr>
                                        <td>{{ $document->getDocumentTypeDisplay() }}</td>
                                        <td>{{ $document->created_at->format('M d, Y') }}</td>
                                        <td>
                                            @if($document->status === 'verified')
                                            <span class="badge bg-success">Verified</span>
                                            @elseif($document->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            @else
                                            <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($document->status !== 'verified')
                                            <form method="POST" action="{{ route('kyc.destroy', $document->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
