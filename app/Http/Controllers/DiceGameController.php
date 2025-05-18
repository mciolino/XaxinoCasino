<?php

namespace App\Http\Controllers;

use App\Models\DiceBet;
use App\Models\Wallet;
use App\Services\ProvablyFairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiceGameController extends Controller
{
    protected $provablyFairService;
    
    public function __construct(ProvablyFairService $provablyFairService)
    {
        $this->provablyFairService = $provablyFairService;
    }
    
    /**
     * Show dice game interface
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user wallets for betting
        $wallets = Wallet::where('user_id', $user->id)->get();
        
        // Get last 10 bets for this user
        $bets = DiceBet::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // Generate a new server seed hash for the next bet
        $serverSeedHash = $this->provablyFairService->generateServerSeedHash();
        
        // Store the seed in session for the next bet
        session(['server_seed' => $this->provablyFairService->getServerSeed()]);
        
        return view('games.dice', [
            'wallets' => $wallets,
            'bets' => $bets,
            'serverSeedHash' => $serverSeedHash
        ]);
    }
    
    /**
     * Process dice game bet
     */
    public function play(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00000001',
            'currency' => 'required|in:BTC,ETH',
            'client_seed' => 'required|string',
            'prediction' => 'required|in:over,under',
            'number' => 'required|numeric|min:1|max:98'
        ]);
        
        $user = Auth::user();
        $amount = $request->amount;
        $currency = $request->currency;
        $clientSeed = $request->client_seed;
        $prediction = $request->prediction;
        $predictedNumber = $request->number;
        
        // Check if user has verified KYC
        if ($user->kyc_status !== 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'You need to complete KYC verification to play.'
            ]);
        }
        
        // Get wallet for the selected currency
        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency', $currency)
            ->first();
            
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => "You don't have a {$currency} wallet."
            ]);
        }
        
        // Check if user has enough balance
        if ($wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.'
            ]);
        }
        
        // Get the server seed from session
        $serverSeed = session('server_seed');
        if (!$serverSeed) {
            // Generate a new one if not found
            $serverSeed = $this->provablyFairService->generateNewServerSeed();
        }
        
        // Calculate the result
        $serverSeedHash = hash('sha256', $serverSeed);
        $combined = hash('sha256', $serverSeed . $clientSeed);
        
        // Extract the first 8 characters of the hash and convert to number between 0-100
        $rolled = hexdec(substr($combined, 0, 8)) % 10000 / 100;
        
        // Determine if the user won based on prediction
        $won = false;
        if ($prediction === 'over' && $rolled > $predictedNumber) {
            $won = true;
        } elseif ($prediction === 'under' && $rolled < $predictedNumber) {
            $won = true;
        }
        
        // Calculate multiplier and payout
        $multiplier = 0;
        if ($prediction === 'over') {
            $multiplier = 99 / (99 - $predictedNumber);
        } else {
            $multiplier = 99 / $predictedNumber;
        }
        
        // Apply house edge (2%)
        $multiplier = $multiplier * 0.98;
        
        // Calculate payout
        $payout = $won ? $amount * $multiplier : 0;
        
        // Save the bet to the database
        $bet = new DiceBet();
        $bet->user_id = $user->id;
        $bet->bet_amount = $amount;
        $bet->currency = $currency;
        $bet->client_seed = $clientSeed;
        $bet->server_seed = $serverSeed;
        $bet->server_seed_hash = $serverSeedHash;
        $bet->roll_result = $rolled;
        $bet->prediction = $prediction;
        $bet->predicted_number = $predictedNumber;
        $bet->multiplier = $multiplier;
        $bet->won = $won;
        $bet->payout = $payout;
        $bet->save();
        
        // Update user's wallet balance
        $wallet->balance = $wallet->balance - $amount + $payout;
        $wallet->save();
        
        // Generate a new server seed hash for the next bet
        $newServerSeedHash = $this->provablyFairService->generateServerSeedHash();
        
        // Store the new seed in session for the next bet
        session(['server_seed' => $this->provablyFairService->getServerSeed()]);
        
        return response()->json([
            'success' => true,
            'bet_id' => $bet->id,
            'rolled' => $rolled,
            'won' => $won,
            'payout' => $payout,
            'updated_balance' => $wallet->balance,
            'server_seed' => $serverSeed,           // Reveal the server seed for the completed bet
            'server_seed_hash' => $serverSeedHash,  // The hash that was shown before the bet
            'next_server_seed_hash' => $newServerSeedHash,  // New hash for the next bet
            'client_seed' => $clientSeed
        ]);
    }
    
    /**
     * Verify the fairness of a specific bet
     */
    public function verify($betId)
    {
        $user = Auth::user();
        $bet = DiceBet::where('id', $betId)->where('user_id', $user->id)->first();
        
        if (!$bet) {
            return view('games.dice_verify', [
                'error' => 'Bet not found'
            ]);
        }
        
        // Recalculate the roll result to verify
        $combined = hash('sha256', $bet->server_seed . $bet->client_seed);
        $calculatedRoll = hexdec(substr($combined, 0, 8)) % 10000 / 100;
        
        // Verify the server seed hash
        $calculatedHash = hash('sha256', $bet->server_seed);
        $hashVerified = ($calculatedHash === $bet->server_seed_hash);
        
        // Check if the stored roll matches the calculated roll
        $rollVerified = abs($calculatedRoll - $bet->roll_result) < 0.00001; // Use a small epsilon for float comparison
        
        return view('games.dice_verify', [
            'bet' => $bet,
            'calculated_roll' => $calculatedRoll,
            'hash_verified' => $hashVerified,
            'roll_verified' => $rollVerified
        ]);
    }
}
