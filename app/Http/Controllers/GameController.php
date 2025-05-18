<?php

namespace App\Http\Controllers;

use App\Models\DiceBet;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GameController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the dice game page.
     *
     * @return \Illuminate\View\View
     */
    public function showDice()
    {
        $user = Auth::user();
        $wallets = $user->wallets;
        $recentBets = $user->diceBets()->latest()->take(10)->get();
        
        return view('games.dice', compact('wallets', 'recentBets'));
    }

    /**
     * Process a dice bet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function playDice(Request $request)
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
        
        // Generate server seed
        $serverSeed = Str::random(32);
        $serverSeedHash = hash('sha256', $serverSeed);
        
        // Generate the roll result using combined seeds
        $combinedSeed = $serverSeed . $request->client_seed;
        $hash = hash('sha256', $combinedSeed);
        $rolledNumber = hexdec(substr($hash, 0, 8)) % 10000 / 100; // 0.00-99.99
        
        // Determine if it's a win (e.g., < 50.00 is a win, with 2x payout)
        $isWin = $rolledNumber < 50.00;
        $multiplier = $isWin ? 2.0 : 0.0;
        $payout = $request->bet_amount * $multiplier;
        
        // Create the bet record
        $bet = new DiceBet([
            'user_id' => $user->id,
            'bet_amount' => $request->bet_amount,
            'client_seed' => $request->client_seed,
            'server_seed' => $serverSeed,
            'server_seed_hash' => $serverSeedHash,
            'rolled_number' => $rolledNumber,
            'payout' => $payout,
            'currency' => $wallet->currency,
        ]);
        
        $bet->save();
        
        // Update the wallet balance
        $wallet->subtractFunds($request->bet_amount);
        
        if ($isWin) {
            $wallet->addFunds($payout);
        }
        
        return response()->json([
            'bet' => $bet,
            'wallet_balance' => $wallet->balance,
            'is_win' => $isWin,
        ]);
    }

    /**
     * Verify the fairness of a previous bet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyDice(Request $request)
    {
        $request->validate([
            'bet_id' => 'required|exists:dice_bets,id',
        ]);

        $user = Auth::user();
        $bet = DiceBet::findOrFail($request->bet_id);
        
        // Ensure the bet belongs to the user
        if ($bet->user_id !== $user->id) {
            return response()->json([
                'error' => 'You can only verify your own bets.'
            ], 403);
        }
        
        // Recalculate the hash
        $combinedSeed = $bet->server_seed . $bet->client_seed;
        $hash = hash('sha256', $combinedSeed);
        $calculatedNumber = hexdec(substr($hash, 0, 8)) % 10000 / 100;
        
        // Verify the result matches
        $isValid = abs($calculatedNumber - $bet->rolled_number) < 0.01;
        
        return response()->json([
            'is_valid' => $isValid,
            'server_seed' => $bet->server_seed,
            'client_seed' => $bet->client_seed,
            'calculated_number' => $calculatedNumber,
            'recorded_number' => $bet->rolled_number,
        ]);
    }

    /**
     * Get the user's recent bets.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentBets()
    {
        $user = Auth::user();
        $recentBets = $user->diceBets()->latest()->take(10)->get();
        
        return response()->json([
            'bets' => $recentBets,
        ]);
    }
}
