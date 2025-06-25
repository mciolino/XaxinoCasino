
<?php

namespace App\Http\Controllers;

use App\Models\SlotBet;
use App\Models\Wallet;
use App\Services\ProvablyFairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SlotGameController extends Controller
{
    protected $provablyFairService;
    
    public function __construct(ProvablyFairService $provablyFairService)
    {
        $this->middleware('auth');
        $this->provablyFairService = $provablyFairService;
    }
    
    /**
     * Show slots game interface
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user wallets for betting
        $wallets = Wallet::where('user_id', $user->id)->get();
        
        // Get last 10 spins for this user
        $recentSpins = SlotBet::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // Generate a new server seed hash for the next spin
        $serverSeedHash = $this->provablyFairService->generateServerSeedHash();
        
        // Store the seed in session for the next spin
        session(['server_seed' => $this->provablyFairService->getServerSeed()]);
        
        return view('games.slots', [
            'wallets' => $wallets,
            'recentSpins' => $recentSpins,
            'serverSeedHash' => $serverSeedHash
        ]);
    }
    
    /**
     * Process slot game spin
     */
    public function spin(Request $request)
    {
        $request->validate([
            'bet_amount' => 'required|numeric|min:0.00000001',
            'client_seed' => 'required|string',
            'wallet_id' => 'required|exists:wallets,id',
        ]);
        
        $user = Auth::user();
        $wallet = Wallet::findOrFail($request->wallet_id);
        
        // Ensure the wallet belongs to the user
        if ($wallet->user_id !== $user->id) {
            return response()->json([
                'error' => 'Invalid wallet selected.'
            ], 403);
        }
        
        // Check if the user has enough balance
        if ($wallet->balance < $request->bet_amount) {
            return response()->json([
                'error' => 'Insufficient balance.'
            ], 403);
        }
        
        // Get the server seed from session or generate a new one
        $serverSeed = session('server_seed');
        if (!$serverSeed) {
            $serverSeed = $this->provablyFairService->generateNewServerSeed();
        }
        $serverSeedHash = hash('sha256', $serverSeed);
        
        // Generate the spin result using combined seeds
        $combinedSeed = $serverSeed . $request->client_seed;
        $hash = hash('sha256', $combinedSeed);
        
        // Use the hash to determine the slot symbols
        $symbols = ['7', 'BAR', 'BELL', 'CHERRY', 'LEMON'];
        $resultSymbols = [];
        
        // Generate 3 symbols using different parts of the hash
        for ($i = 0; $i < 3; $i++) {
            $segment = substr($hash, $i * 8, 8);
            $index = hexdec($segment) % count($symbols);
            $resultSymbols[] = $symbols[$index];
        }
        
        // Determine win and multiplier
        $multiplier = 0;
        $isWin = false;
        
        // All 3 matching symbols
        if ($resultSymbols[0] === $resultSymbols[1] && $resultSymbols[1] === $resultSymbols[2]) {
            $isWin = true;
            
            // Different multipliers based on symbol
            switch($resultSymbols[0]) {
                case '7':
                    $multiplier = 10;
                    break;
                case 'BAR':
                    $multiplier = 5;
                    break;
                case 'BELL':
                    $multiplier = 4;
                    break;
                case 'CHERRY':
                    $multiplier = 3;
                    break;
                case 'LEMON':
                    $multiplier = 2;
                    break;
            }
        }
        // Two matching symbols (any position)
        else if ($resultSymbols[0] === $resultSymbols[1] || 
                $resultSymbols[1] === $resultSymbols[2] || 
                $resultSymbols[0] === $resultSymbols[2]) {
            $isWin = true;
            $multiplier = 1.5;
        }
        
        // Calculate payout
        $payout = $isWin ? $request->bet_amount * $multiplier : 0;
        
        // Create bet record
        $bet = new SlotBet([
            'user_id' => $user->id,
            'bet_amount' => $request->bet_amount,
            'client_seed' => $request->client_seed,
            'server_seed' => $serverSeed,
            'server_seed_hash' => $serverSeedHash,
            'result_symbols' => implode(',', $resultSymbols),
            'multiplier' => $multiplier,
            'payout' => $payout,
            'currency' => $wallet->currency,
        ]);
        
        $bet->save();
        
        // Update wallet balance
        $wallet->subtractFunds($request->bet_amount);
        
        if ($isWin) {
            $wallet->addFunds($payout);
        }
        
        // Generate a new server seed for next spin
        $newServerSeed = $this->provablyFairService->generateNewServerSeed();
        $newServerSeedHash = hash('sha256', $newServerSeed);
        session(['server_seed' => $newServerSeed]);
        
        return response()->json([
            'success' => true,
            'bet_id' => $bet->id,
            'result_symbols' => $resultSymbols,
            'is_win' => $isWin,
            'multiplier' => $multiplier,
            'payout' => $payout,
            'wallet_balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'server_seed' => $serverSeed,
            'client_seed' => $request->client_seed,
            'hash' => $hash,
            'server_seed_hash' => $serverSeedHash,
            'next_server_seed_hash' => $newServerSeedHash,
            'bet_amount' => $request->bet_amount
        ]);
    }
    
    /**
     * Verify the fairness of a specific spin
     */
    public function verify($betId)
    {
        $user = Auth::user();
        $bet = SlotBet::where('id', $betId)->where('user_id', $user->id)->first();
        
        if (!$bet) {
            return view('games.slots_verify', [
                'error' => 'Bet not found'
            ]);
        }
        
        // Recalculate the spin result to verify
        $combinedSeed = hash('sha256', $bet->server_seed . $bet->client_seed);
        
        // Extract symbols from stored result
        $storedSymbols = explode(',', $bet->result_symbols);
        
        // Recalculate symbols
        $symbols = ['7', 'BAR', 'BELL', 'CHERRY', 'LEMON'];
        $calculatedSymbols = [];
        
        for ($i = 0; $i < 3; $i++) {
            $segment = substr($combinedSeed, $i * 8, 8);
            $index = hexdec($segment) % count($symbols);
            $calculatedSymbols[] = $symbols[$index];
        }
        
        // Verify the server seed hash
        $calculatedHash = hash('sha256', $bet->server_seed);
        $hashVerified = ($calculatedHash === $bet->server_seed_hash);
        
        // Check if the stored symbols match the calculated symbols
        $symbolsVerified = ($storedSymbols === $calculatedSymbols);
        
        return view('games.slots_verify', [
            'bet' => $bet,
            'calculated_symbols' => $calculatedSymbols,
            'hash_verified' => $hashVerified,
            'symbols_verified' => $symbolsVerified
        ]);
    }
}
