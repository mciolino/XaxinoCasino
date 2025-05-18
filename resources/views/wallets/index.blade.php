@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="wallet-overview-card">
                <div class="card-header">
                    <h2>My Cryptocurrency Wallets</h2>
                </div>

                <div class="card-body">
                    @if(count($wallets) === 0)
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-wallet fa-5x text-muted"></i>
                        </div>
                        <h3>No wallets found</h3>
                        <p class="mb-4">You don't have any cryptocurrency wallets set up yet.</p>
                        <a href="{{ route('wallets.generate') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle me-2"></i> Generate Wallets
                        </a>
                    </div>
                    @else
                    <div class="row">
                        @foreach($wallets as $wallet)
                        <div class="col-md-6 mb-4">
                            <div class="wallet-card {{ strtolower($wallet->currency) }}-wallet">
                                <div class="wallet-card-header">
                                    <div class="wallet-icon">
                                        @if($wallet->currency === 'BTC')
                                        <i class="fab fa-bitcoin"></i>
                                        @elseif($wallet->currency === 'ETH')
                                        <i class="fab fa-ethereum"></i>
                                        @else
                                        <i class="fas fa-coins"></i>
                                        @endif
                                    </div>
                                    <div class="wallet-title">
                                        <h3>{{ $wallet->currency }} Wallet</h3>
                                        <div class="wallet-balance">
                                            {{ $wallet->formattedBalance() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="wallet-card-body">
                                    <div class="wallet-address mb-3">
                                        <label>Wallet Address</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ $wallet->address }}" readonly>
                                            <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="{{ $wallet->address }}">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="wallet-actions">
                                        <a href="{{ route('wallets.deposit', $wallet->currency) }}" class="btn btn-success me-2">
                                            <i class="fas fa-arrow-down me-1"></i> Deposit
                                        </a>
                                        <a href="{{ route('wallets.withdraw', $wallet->currency) }}" class="btn btn-primary">
                                            <i class="fas fa-arrow-up me-1"></i> Withdraw
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="section-divider my-4"></div>

                    <div class="transaction-history-section">
                        <h3>Recent Transactions</h3>
                        
                        <div class="table-responsive">
                            <table class="table table-striped transaction-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i> No transactions yet
                                            </div>
                                        </td>
                                    </tr>
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

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new ClipboardJS('.copy-btn');
        
        // Add tooltip functionality
        const copyButtons = document.querySelectorAll('.copy-btn');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });
</script>
@endpush
