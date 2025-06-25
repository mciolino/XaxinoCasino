
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-check-circle"></i> Verify Slot Spin Fairness</h4>
                </div>
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @else
                        <div class="verification-details">
                            <h5 class="mb-4">Spin Details</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Bet ID:</strong></div>
                                <div class="col-md-9">{{ $bet->id }}</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Time:</strong></div>
                                <div class="col-md-9">{{ $bet->created_at }}</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Server Seed:</strong></div>
                                <div class="col-md-9"><code>{{ $bet->server_seed }}</code></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Server Seed Hash:</strong></div>
                                <div class="col-md-9"><code>{{ $bet->server_seed_hash }}</code></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Client Seed:</strong></div>
                                <div class="col-md-9"><code>{{ $bet->client_seed }}</code></div>
                            </div>
                            
                            <h5 class="mt-5 mb-4">Verification Results</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Stored Symbols:</strong></div>
                                <div class="col-md-9"><code>{{ $bet->result_symbols }}</code></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Calculated Symbols:</strong></div>
                                <div class="col-md-9"><code>{{ implode(', ', $calculated_symbols) }}</code></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Server Seed Hash Verification:</strong></div>
                                <div class="col-md-9">
                                    @if($hash_verified)
                                        <span class="badge bg-success">Valid</span>
                                    @else
                                        <span class="badge bg-danger">Invalid</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Symbols Verification:</strong></div>
                                <div class="col-md-9">
                                    @if($symbols_verified)
                                        <span class="badge bg-success">Valid</span>
                                    @else
                                        <span class="badge bg-danger">Invalid</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3"><strong>Final Result:</strong></div>
                                <div class="col-md-9">
                                    @if($hash_verified && $symbols_verified)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-danger">Failed Verification</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="verification-explanation mt-5">
                            <h5>How Verification Works</h5>
                            <p>To verify the fairness of this spin:</p>
                            <ol>
                                <li>We combined the server seed <code>{{ $bet->server_seed }}</code> with your client seed <code>{{ $bet->client_seed }}</code></li>
                                <li>We applied SHA-256 to this combined value to get a hash</li>
                                <li>We used segments of this hash to determine the slot symbols</li>
                                <li>The results are compared with what was recorded at the time of the spin</li>
                            </ol>
                            <p>This verification confirms that the results were not altered after the bet was placed.</p>
                        </div>
                    @endif
                    
                    <div class="text-center mt-4">
                        <a href="{{ route('games.slots') }}" class="btn btn-primary">Back to Slot Game</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
