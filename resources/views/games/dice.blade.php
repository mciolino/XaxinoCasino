@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="dice-game-card">
                <div class="card-header">
                    <h2><i class="fas fa-dice me-2"></i> Dice Game</h2>
                </div>
                <div class="card-body">
                    <div class="dice-game-container">
                        <div class="dice-result-container mb-4" id="diceResultContainer">
                            <div class="dice-placeholder" id="dicePlaceholder">
                                <i class="fas fa-dice-five fa-5x"></i>
                                <p>Roll the dice to play!</p>
                            </div>
                            <div class="dice-result d-none" id="diceResult">
                                <div class="result-number" id="resultNumber">00.00</div>
                                <div class="result-status">
                                    <span class="win-badge d-none" id="winBadge">WIN!</span>
                                    <span class="lose-badge d-none" id="loseBadge">LOSE!</span>
                                </div>
                            </div>
                        </div>

                        <div class="dice-game-form">
                            <form id="diceGameForm">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="betAmount" class="form-label">Bet Amount</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="betAmount" step="0.00000001" min="0.00000001" required>
                                            <select class="form-select wallet-currency" id="walletSelector" style="max-width: 100px;">
                                                @foreach($wallets as $wallet)
                                                <option value="{{ $wallet->id }}" data-balance="{{ $wallet->balance }}">{{ $wallet->currency }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-text">
                                            Balance: <span id="walletBalance">Loading...</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="clientSeed" class="form-label">Client Seed <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="This seed is combined with the server seed to generate your roll result"></i></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="clientSeed" required>
                                            <button class="btn btn-outline-secondary" type="button" id="generateSeedBtn">
                                                <i class="fas fa-random"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label">Win Chance</label>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">0%</span>
                                            <input type="range" class="form-range flex-grow-1" id="winChanceSlider" min="1" max="95" step="1" value="50">
                                            <span class="ms-2"><span id="winChanceValue">50</span>%</span>
                                        </div>
                                        <div class="form-text text-center">
                                            Roll under <span id="targetNumber">50.00</span> to win <span id="multiplierValue">2.00</span>x
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" id="rollButton">
                                        <i class="fas fa-dice"></i> Roll Dice
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="provably-fair-card mt-4">
                <div class="card-header">
                    <h4>Provably Fair System</h4>
                </div>
                <div class="card-body">
                    <p>Our dice game uses a provably fair algorithm to ensure complete transparency and fairness:</p>
                    <ol>
                        <li>Before your roll, we create a <strong>server seed</strong> and send you its hash.</li>
                        <li>You provide a <strong>client seed</strong> of your choice.</li>
                        <li>The roll result is calculated using: <code>SHA256(server_seed + client_seed)</code></li>
                        <li>After the roll, we reveal the original server seed so you can verify the result.</li>
                    </ol>
                    <div class="verification-tool mt-3 d-none" id="verificationTool">
                        <h5>Verify Your Last Roll</h5>
                        <div class="verification-data">
                            <div class="row mb-2">
                                <div class="col-md-4"><strong>Server Seed:</strong></div>
                                <div class="col-md-8"><code id="serverSeedValue">N/A</code></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4"><strong>Client Seed:</strong></div>
                                <div class="col-md-8"><code id="clientSeedValue">N/A</code></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4"><strong>Result:</strong></div>
                                <div class="col-md-8"><code id="rollResultValue">N/A</code></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="bet-history-card">
                <div class="card-header">
                    <h4>Recent Bets</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table bet-history-table mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Bet</th>
                                    <th>Roll</th>
                                    <th>Payout</th>
                                </tr>
                            </thead>
                            <tbody id="betHistoryTable">
                                @forelse($recentBets as $bet)
                                <tr class="{{ $bet->isWin() ? 'bet-win' : 'bet-lose' }}">
                                    <td>{{ $bet->created_at->format('H:i:s') }}</td>
                                    <td>{{ number_format($bet->bet_amount, 8) }} {{ $bet->currency }}</td>
                                    <td>{{ number_format($bet->rolled_number, 2) }}</td>
                                    <td>{{ number_format($bet->payout, 8) }} {{ $bet->currency }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No bets yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="stats-card mt-4">
                <div class="card-header">
                    <h4>Game Stats</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">
                            <div class="stat-label">Bets Made</div>
                            <div class="stat-value" id="totalBets">{{ count($recentBets) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Win Rate</div>
                            <div class="stat-value" id="winRate">
                                @php
                                $winCount = $recentBets->filter(function($bet) { return $bet->isWin(); })->count();
                                $winRate = count($recentBets) > 0 ? round(($winCount / count($recentBets)) * 100) : 0;
                                @endphp
                                {{ $winRate }}%
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="stat-label">Best Win</div>
                            <div class="stat-value" id="bestWin">
                                @php
                                $bestWin = $recentBets->where('payout', '>', 0)->max('payout') ?? 0;
                                @endphp
                                {{ number_format($bestWin, 8) }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Wagered</div>
                            <div class="stat-value" id="totalWagered">
                                @php
                                $totalWagered = $recentBets->sum('bet_amount');
                                @endphp
                                {{ number_format($totalWagered, 8) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/js/dice-game.js"></script>
@endpush
