/**
 * Dice Game - Client-side JavaScript
 * Handles the provably fair dice game functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const diceGameForm = document.getElementById('diceGameForm');
    const rollButton = document.getElementById('rollButton');
    const betAmount = document.getElementById('betAmount');
    const clientSeed = document.getElementById('clientSeed');
    const walletSelector = document.getElementById('walletSelector');
    const walletBalance = document.getElementById('walletBalance');
    const generateSeedBtn = document.getElementById('generateSeedBtn');
    const winChanceSlider = document.getElementById('winChanceSlider');
    const winChanceValue = document.getElementById('winChanceValue');
    const targetNumber = document.getElementById('targetNumber');
    const multiplierValue = document.getElementById('multiplierValue');
    
    const dicePlaceholder = document.getElementById('dicePlaceholder');
    const diceResult = document.getElementById('diceResult');
    const resultNumber = document.getElementById('resultNumber');
    const winBadge = document.getElementById('winBadge');
    const loseBadge = document.getElementById('loseBadge');
    
    const verificationTool = document.getElementById('verificationTool');
    const serverSeedValue = document.getElementById('serverSeedValue');
    const clientSeedValue = document.getElementById('clientSeedValue');
    const rollResultValue = document.getElementById('rollResultValue');
    
    const betHistoryTable = document.getElementById('betHistoryTable');
    
    // Game state
    let currentWallet = null;
    let currentBalance = 0;
    let lastBet = null;
    
    // Initialize
    function init() {
        // Generate random seed
        clientSeed.value = generateRandomSeed();
        
        // Set initial wallet
        if (walletSelector.options.length > 0) {
            currentWallet = walletSelector.options[0].value;
            currentBalance = parseFloat(walletSelector.options[0].dataset.balance);
            updateBalanceDisplay();
        }
        
        // Set events
        diceGameForm.addEventListener('submit', handleSubmit);
        generateSeedBtn.addEventListener('click', handleGenerateSeed);
        walletSelector.addEventListener('change', handleWalletChange);
        winChanceSlider.addEventListener('input', handleWinChanceChange);
        
        // Initial win chance update
        handleWinChanceChange();
    }
    
    // Handle form submission
    async function handleSubmit(e) {
        e.preventDefault();
        
        // Validate bet amount
        const amount = parseFloat(betAmount.value);
        if (isNaN(amount) || amount <= 0 || amount > currentBalance) {
            showError('Invalid bet amount');
            return;
        }
        
        // Disable form during bet
        setFormState(false);
        
        try {
            // Send bet to server
            const response = await fetch('/games/dice/play', {
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
            
            const data = await response.json();
            
            if (data.error) {
                showError(data.error);
                return;
            }
            
            // Update balance
            currentBalance = data.wallet_balance;
            updateBalanceDisplay();
            
            // Show result
            displayResult(data.bet, data.is_win);
            
            // Update bet history
            updateBetHistory(data.bet);
            
            // Save last bet for verification
            lastBet = data.bet;
            
            // Show verification tool
            updateVerificationTool(data.bet);
            
        } catch (error) {
            console.error('Error:', error);
            showError('An error occurred while processing your bet');
        } finally {
            setFormState(true);
        }
    }
    
    // Display the roll result
    function displayResult(bet, isWin) {
        // Hide placeholder, show result
        dicePlaceholder.classList.add('d-none');
        diceResult.classList.remove('d-none');
        
        // Show roll number
        resultNumber.textContent = bet.rolled_number.toFixed(2);
        
        // Show win/lose badge
        if (isWin) {
            winBadge.classList.remove('d-none');
            loseBadge.classList.add('d-none');
            diceResult.classList.add('win-result');
            diceResult.classList.remove('lose-result');
        } else {
            winBadge.classList.add('d-none');
            loseBadge.classList.remove('d-none');
            diceResult.classList.add('lose-result');
            diceResult.classList.remove('win-result');
        }
        
        // Animate the result
        animateResult();
    }
    
    // Animate the dice roll result
    function animateResult() {
        diceResult.classList.add('animate-result');
        setTimeout(() => {
            diceResult.classList.remove('animate-result');
        }, 1000);
    }
    
    // Reset the game for next roll
    function resetGame() {
        // Hide result, show placeholder
        diceResult.classList.add('d-none');
        dicePlaceholder.classList.remove('d-none');
        
        // Generate new client seed
        clientSeed.value = generateRandomSeed();
    }
    
    // Update the bet history table
    function updateBetHistory(bet) {
        // Create new row
        const row = document.createElement('tr');
        row.className = bet.payout > bet.bet_amount ? 'bet-win' : 'bet-lose';
        
        // Format time
        const time = new Date(bet.created_at).toLocaleTimeString();
        
        // Add cells
        row.innerHTML = `
            <td>${time}</td>
            <td>${bet.bet_amount.toFixed(8)} ${bet.currency}</td>
            <td>${bet.rolled_number.toFixed(2)}</td>
            <td>${bet.payout.toFixed(8)} ${bet.currency}</td>
        `;
        
        // Add to table
        if (betHistoryTable.querySelector('td[colspan="4"]')) {
            // Remove "no bets" message
            betHistoryTable.innerHTML = '';
        }
        
        // Add to top of table
        betHistoryTable.insertBefore(row, betHistoryTable.firstChild);
        
        // Limit to 10 rows
        const rows = betHistoryTable.querySelectorAll('tr');
        if (rows.length > 10) {
            betHistoryTable.removeChild(rows[rows.length - 1]);
        }
        
        // Update stats
        updateGameStats();
    }
    
    // Update game statistics
    function updateGameStats() {
        const totalBetsEl = document.getElementById('totalBets');
        const winRateEl = document.getElementById('winRate');
        const bestWinEl = document.getElementById('bestWin');
        const totalWageredEl = document.getElementById('totalWagered');
        
        // Get all rows
        const rows = betHistoryTable.querySelectorAll('tr');
        const totalBets = rows.length;
        
        // Count wins
        const winRows = betHistoryTable.querySelectorAll('tr.bet-win');
        const winRate = totalBets > 0 ? Math.round((winRows.length / totalBets) * 100) : 0;
        
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
        totalBetsEl.textContent = totalBets;
        winRateEl.textContent = `${winRate}%`;
        bestWinEl.textContent = bestWin.toFixed(8);
        totalWageredEl.textContent = totalWagered.toFixed(8);
    }
    
    // Update verification tool with bet data
    function updateVerificationTool(bet) {
        serverSeedValue.textContent = bet.server_seed;
        clientSeedValue.textContent = bet.client_seed;
        rollResultValue.textContent = bet.rolled_number.toFixed(2);
        
        verificationTool.classList.remove('d-none');
    }
    
    // Generate a random seed
    function generateRandomSeed() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 16; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    // Handle generate seed button click
    function handleGenerateSeed() {
        clientSeed.value = generateRandomSeed();
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
    
    // Handle win chance slider change
    function handleWinChanceChange() {
        const chance = parseInt(winChanceSlider.value);
        winChanceValue.textContent = chance;
        targetNumber.textContent = chance.toFixed(2);
        
        // Calculate multiplier (house edge 2%)
        const multiplier = (100 - 2) / chance;
        multiplierValue.textContent = multiplier.toFixed(2);
    }
    
    // Show error message
    function showError(message) {
        alert(message);
    }
    
    // Enable/disable form during bet
    function setFormState(enabled) {
        betAmount.disabled = !enabled;
        clientSeed.disabled = !enabled;
        walletSelector.disabled = !enabled;
        winChanceSlider.disabled = !enabled;
        rollButton.disabled = !enabled;
        
        if (!enabled) {
            rollButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rolling...';
        } else {
            rollButton.innerHTML = '<i class="fas fa-dice"></i> Roll Dice';
        }
    }
    
    // Initialize
    init();
});
