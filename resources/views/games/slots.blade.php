
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="slot-machine mb-4">
                <div class="slot-header">
                    <h1 class="slot-title">Lucky Spin Slots</h1>
                </div>
                
                <div class="slot-display">
                    <div class="slot-reels">
                        <div class="slot-reel" id="reel1">
                            <div class="slot-symbol symbol-7">7</div>
                        </div>
                        <div class="slot-reel" id="reel2">
                            <div class="slot-symbol symbol-7">7</div>
                        </div>
                        <div class="slot-reel" id="reel3">
                            <div class="slot-symbol symbol-7">7</div>
                        </div>
                    </div>
                    
                    <div class="slot-result" id="slotResult">
                        <div class="slot-result-message" id="resultMessage"></div>
                        <button class="btn btn-primary btn-lg" id="playAgainBtn">Play Again</button>
                    </div>
                </div>
                
                <div class="slot-controls">
                    <form id="slotForm">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="betAmount" class="form-label">Bet Amount</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="betAmount" step="0.00000001" min="0.00000001" required>
                                    <select class="form-select" id="walletSelector" style="max-width: 100px;">
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
                                <label for="clientSeed" class="form-label">Client Seed <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="This seed is combined with the server seed to generate your spin result"></i></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="clientSeed" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generateSeedBtn">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="spinButton">
                                <i class="fas fa-play-circle"></i> Spin Reels
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Paytable</h4>
                </div>
                <div class="card-body">
                    <div class="paytable">
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                <span class="mini-symbol symbol-7">7</span>
                                <span class="mini-symbol symbol-7">7</span>
                                <span class="mini-symbol symbol-7">7</span>
                            </div>
                            <div class="paytable-multiplier">10x</div>
                        </div>
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                <span class="mini-symbol symbol-bar">BAR</span>
                                <span class="mini-symbol symbol-bar">BAR</span>
                                <span class="mini-symbol symbol-bar">BAR</span>
                            </div>
                            <div class="paytable-multiplier">5x</div>
                        </div>
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                <span class="mini-symbol symbol-bell">🔔</span>
                                <span class="mini-symbol symbol-bell">🔔</span>
                                <span class="mini-symbol symbol-bell">🔔</span>
                            </div>
                            <div class="paytable-multiplier">4x</div>
                        </div>
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                <span class="mini-symbol symbol-cherry">🍒</span>
                                <span class="mini-symbol symbol-cherry">🍒</span>
                                <span class="mini-symbol symbol-cherry">🍒</span>
                            </div>
                            <div class="paytable-multiplier">3x</div>
                        </div>
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                <span class="mini-symbol symbol-lemon">🍋</span>
                                <span class="mini-symbol symbol-lemon">🍋</span>
                                <span class="mini-symbol symbol-lemon">🍋</span>
                            </div>
                            <div class="paytable-multiplier">2x</div>
                        </div>
                        <div class="paytable-row">
                            <div class="paytable-combo">
                                Any Two Matching Symbols
                            </div>
                            <div class="paytable-multiplier">1.5x</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4>Provably Fair System</h4>
                </div>
                <div class="card-body">
                    <p>Our slot machine uses a provably fair algorithm to ensure complete transparency and fairness:</p>
                    <ol>
                        <li>Before your spin, we create a <strong>server seed</strong> and send you its hash.</li>
                        <li>You provide a <strong>client seed</strong> of your choice.</li>
                        <li>The spin result is calculated using: <code>SHA256(server_seed + client_seed)</code></li>
                        <li>After the spin, we reveal the original server seed so you can verify the result.</li>
                    </ol>
                    <div class="mt-3 d-none" id="verificationTool">
                        <h5>Verify Your Last Spin</h5>
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
                                <div class="col-md-4"><strong>Result Hash:</strong></div>
                                <div class="col-md-8"><code id="resultHashValue">N/A</code></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Recent Spins</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table bet-history-table mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Bet</th>
                                    <th>Result</th>
                                    <th>Payout</th>
                                </tr>
                            </thead>
                            <tbody id="spinHistoryTable">
                                @forelse($recentSpins as $spin)
                                <tr class="{{ $spin->isWin() ? 'bet-win' : 'bet-lose' }}">
                                    <td>{{ $spin->created_at->format('H:i:s') }}</td>
                                    <td>{{ number_format($spin->bet_amount, 8) }} {{ $spin->currency }}</td>
                                    <td>{{ $spin->result_symbols }}</td>
                                    <td>{{ number_format($spin->payout, 8) }} {{ $spin->currency }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No spins yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Game Stats</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">
                            <div class="stat-label">Spins</div>
                            <div class="stat-value" id="totalSpins">{{ count($recentSpins) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Win Rate</div>
                            <div class="stat-value" id="winRate">
                                @php
                                $winCount = $recentSpins->filter(function($spin) { return $spin->isWin(); })->count();
                                $winRate = count($recentSpins) > 0 ? round(($winCount / count($recentSpins)) * 100) : 0;
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
                                $bestWin = $recentSpins->where('payout', '>', 0)->max('payout') ?? 0;
                                @endphp
                                {{ number_format($bestWin, 8) }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-label">Wagered</div>
                            <div class="stat-value" id="totalWagered">
                                @php
                                $totalWagered = $recentSpins->sum('bet_amount');
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const slotForm = document.getElementById('slotForm');
    const spinButton = document.getElementById('spinButton');
    const betAmount = document.getElementById('betAmount');
    const clientSeed = document.getElementById('clientSeed');
    const walletSelector = document.getElementById('walletSelector');
    const walletBalance = document.getElementById('walletBalance');
    const generateSeedBtn = document.getElementById('generateSeedBtn');
    
    const reel1 = document.getElementById('reel1');
    const reel2 = document.getElementById('reel2');
    const reel3 = document.getElementById('reel3');
    
    const slotResult = document.getElementById('slotResult');
    const resultMessage = document.getElementById('resultMessage');
    const playAgainBtn = document.getElementById('playAgainBtn');
    
    const verificationTool = document.getElementById('verificationTool');
    const serverSeedValue = document.getElementById('serverSeedValue');
    const clientSeedValue = document.getElementById('clientSeedValue');
    const resultHashValue = document.getElementById('resultHashValue');
    
    const spinHistoryTable = document.getElementById('spinHistoryTable');
    
    // Symbols for the slot machine
    const symbols = {
        '7': '<div class="slot-symbol symbol-7">7</div>',
        'BAR': '<div class="slot-symbol symbol-bar">BAR</div>',
        'BELL': '<div class="slot-symbol symbol-bell">🔔</div>',
        'CHERRY': '<div class="slot-symbol symbol-cherry">🍒</div>',
        'LEMON': '<div class="slot-symbol symbol-lemon">🍋</div>'
    };
    
    // Game state
    let currentWallet = null;
    let currentBalance = 0;
    let isSpinning = false;
    
    // Initialize
    function init() {
        // Generate random seed
        clientSeed.value = generateRandomString();
        
        // Set initial wallet
        if (walletSelector.options.length > 0) {
            currentWallet = walletSelector.options[0].value;
            currentBalance = parseFloat(walletSelector.options[0].dataset.balance);
            updateBalanceDisplay();
        }
        
        // Set events
        slotForm.addEventListener('submit', handleSubmit);
        generateSeedBtn.addEventListener('click', handleGenerateSeed);
        walletSelector.addEventListener('change', handleWalletChange);
        playAgainBtn.addEventListener('click', resetGame);
    }
    
    // Handle form submission
    async function handleSubmit(e) {
        e.preventDefault();
        
        if (isSpinning) return;
        
        // Validate bet amount
        const amount = parseFloat(betAmount.value);
        if (isNaN(amount) || amount <= 0 || amount > currentBalance) {
            showError('Invalid bet amount');
            return;
        }
        
        // Disable form during spin
        isSpinning = true;
        setFormState(false);
        
        try {
            // Start spinning animation
            startSpinAnimation();
            
            // Send bet to server
            const response = await fetch('/games/slots/spin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    bet_amount: amount,
                    client_seed: clientSeed.value,
                    wallet_id: currentWallet
                })
            });
            
            // Wait for spinning animation
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            const data = await response.json();
            
            if (data.error) {
                showError(data.error);
                stopSpinAnimation();
                resetGame();
                return;
            }
            
            // Update balance
            currentBalance = data.wallet_balance;
            updateBalanceDisplay();
            
            // Show result
            stopSpinAnimation(data.result_symbols);
            displayResult(data.is_win, data.payout, data.multiplier);
            
            // Update verification tool
            updateVerificationTool(data);
            
            // Update spin history
            updateSpinHistory(data);
            
            // Update stats
            updateGameStats();
            
        } catch (error) {
            console.error('Error:', error);
            showError('An error occurred while processing your bet');
            stopSpinAnimation();
            resetGame();
        }
    }
    
    // Start spinning animation
    function startSpinAnimation() {
        reel1.innerHTML = '<div class="slot-symbol reel-spinning">💫</div>';
        reel2.innerHTML = '<div class="slot-symbol reel-spinning">💫</div>';
        reel3.innerHTML = '<div class="slot-symbol reel-spinning">💫</div>';
    }
    
    // Stop spinning animation and show result
    function stopSpinAnimation(resultSymbols) {
        if (!resultSymbols) {
            resultSymbols = ['7', '7', '7']; // Default
        }
        
        reel1.innerHTML = symbols[resultSymbols[0]] || symbols['7'];
        reel2.innerHTML = symbols[resultSymbols[1]] || symbols['7'];
        reel3.innerHTML = symbols[resultSymbols[2]] || symbols['7'];
    }
    
    // Display the spin result
    function displayResult(isWin, payout, multiplier) {
        slotResult.classList.add('show');
        
        if (isWin) {
            resultMessage.textContent = `You Won ${payout.toFixed(8)}! (${multiplier}x)`;
            resultMessage.className = 'slot-result-message win-message';
        } else {
            resultMessage.textContent = 'No Win. Try Again!';
            resultMessage.className = 'slot-result-message lose-message';
        }
    }
    
    // Reset game for another spin
    function resetGame() {
        slotResult.classList.remove('show');
        isSpinning = false;
        setFormState(true);
        clientSeed.value = generateRandomString();
    }
    
    // Update verification tool with spin data
    function updateVerificationTool(data) {
        serverSeedValue.textContent = data.server_seed;
        clientSeedValue.textContent = data.client_seed;
        resultHashValue.textContent = data.hash;
        
        verificationTool.classList.remove('d-none');
    }
    
    // Update spin history table
    function updateSpinHistory(data) {
        const row = document.createElement('tr');
        row.className = data.is_win ? 'bet-win' : 'bet-lose';
        
        const date = new Date();
        const time = date.toLocaleTimeString();
        
        row.innerHTML = `
            <td>${time}</td>
            <td>${data.bet_amount.toFixed(8)} ${data.currency}</td>
            <td>${data.result_symbols.join(', ')}</td>
            <td>${data.payout.toFixed(8)} ${data.currency}</td>
        `;
        
        // Add to top of table
        const emptyRow = spinHistoryTable.querySelector('td[colspan="4"]');
        if (emptyRow) {
            spinHistoryTable.innerHTML = '';
        }
        
        spinHistoryTable.insertBefore(row, spinHistoryTable.firstChild);
        
        // Limit to 10 rows
        const rows = spinHistoryTable.querySelectorAll('tr');
        if (rows.length > 10) {
            spinHistoryTable.removeChild(rows[rows.length - 1]);
        }
    }
    
    // Update game stats
    function updateGameStats() {
        const totalSpinsEl = document.getElementById('totalSpins');
        const winRateEl = document.getElementById('winRate');
        const bestWinEl = document.getElementById('bestWin');
        const totalWageredEl = document.getElementById('totalWagered');
        
        // Get all rows
        const rows = spinHistoryTable.querySelectorAll('tr');
        const totalSpins = rows.length;
        
        // Count wins
        const winRows = spinHistoryTable.querySelectorAll('tr.bet-win');
        const winRate = totalSpins > 0 ? Math.round((winRows.length / totalSpins) * 100) : 0;
        
        // Calculate best win and total wagered
        let bestWin = 0;
        let totalWagered = 0;
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 4) {
                const betAmount = parseFloat(cells[1].textContent.split(' ')[0]);
                const payout = parseFloat(cells[3].textContent.split(' ')[0]);
                
                totalWagered += betAmount;
                
                if (payout > bestWin) {
                    bestWin = payout;
                }
            }
        });
        
        // Update display
        totalSpinsEl.textContent = totalSpins;
        winRateEl.textContent = `${winRate}%`;
        bestWinEl.textContent = bestWin.toFixed(8);
        totalWageredEl.textContent = totalWagered.toFixed(8);
    }
    
    // Generate a random seed
    function generateRandomString(length = 16) {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    // Handle generate seed button click
    function handleGenerateSeed() {
        clientSeed.value = generateRandomString();
    }
    
    // Handle wallet change
    function handleWalletChange() {
        const selectedOption = walletSelector.options[walletSelector.selectedIndex];
        currentWallet = selectedOption.value;
        currentBalance = parseFloat(selectedOption.dataset.balance);
        updateBalanceDisplay();
    }
    
    // Update balance display
    function updateBalanceDisplay() {
        if (currentBalance !== null) {
            const currency = walletSelector.options[walletSelector.selectedIndex].text;
            walletBalance.textContent = `${currentBalance.toFixed(8)} ${currency}`;
        } else {
            walletBalance.textContent = 'No wallet selected';
        }
    }
    
    // Show error message
    function showError(message) {
        alert(message);
    }
    
    // Enable/disable form during spin
    function setFormState(enabled) {
        betAmount.disabled = !enabled;
        clientSeed.disabled = !enabled;
        walletSelector.disabled = !enabled;
        spinButton.disabled = !enabled;
        
        if (!enabled) {
            spinButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Spinning...';
        } else {
            spinButton.innerHTML = '<i class="fas fa-play-circle"></i> Spin Reels';
        }
    }
    
    // Initialize
    init();
});
</script>
@endpush
