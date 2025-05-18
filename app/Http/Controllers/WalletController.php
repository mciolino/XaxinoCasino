<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\BtcWalletService;
use App\Services\EthWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $btcWalletService;
    protected $ethWalletService;

    /**
     * Create a new controller instance.
     *
     * @param BtcWalletService $btcWalletService
     * @param EthWalletService $ethWalletService
     */
    public function __construct(BtcWalletService $btcWalletService, EthWalletService $ethWalletService)
    {
        $this->middleware('auth');
        $this->btcWalletService = $btcWalletService;
        $this->ethWalletService = $ethWalletService;
    }

    /**
     * Display a listing of the wallets.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $wallets = $user->wallets;
        
        return view('wallets.index', compact('wallets'));
    }

    /**
     * Show the wallet setup page.
     *
     * @return \Illuminate\View\View
     */
    public function setup()
    {
        return view('wallets.setup');
    }

    /**
     * Generate wallets for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        $user = Auth::user();
        
        // Check if user already has wallets
        if ($user->wallets()->count() > 0) {
            return redirect()->route('wallets.index')
                ->with('info', 'You already have wallets generated.');
        }
        
        // Generate BTC wallet
        $btcWallet = $this->btcWalletService->generateWallet();
        $user->wallets()->create([
            'currency' => 'BTC',
            'address' => $btcWallet['address'],
            'private_key' => $btcWallet['privateKey'],
            'balance' => 0.0,
        ]);
        
        // Generate ETH wallet
        $ethWallet = $this->ethWalletService->generateWallet();
        $user->wallets()->create([
            'currency' => 'ETH',
            'address' => $ethWallet['address'],
            'private_key' => $ethWallet['privateKey'],
            'balance' => 0.0,
        ]);
        
        return redirect()->route('wallets.index')
            ->with('success', 'Your cryptocurrency wallets have been generated successfully!');
    }

    /**
     * Show the deposit form.
     *
     * @param  string  $currency
     * @return \Illuminate\View\View
     */
    public function showDeposit($currency)
    {
        $user = Auth::user();
        $wallet = $user->wallets()->where('currency', strtoupper($currency))->firstOrFail();
        
        return view('wallets.deposit', compact('wallet'));
    }

    /**
     * Show the withdrawal form.
     *
     * @param  string  $currency
     * @return \Illuminate\View\View
     */
    public function showWithdrawal($currency)
    {
        $user = Auth::user();
        $wallet = $user->wallets()->where('currency', strtoupper($currency))->firstOrFail();
        
        return view('wallets.withdraw', compact('wallet'));
    }

    /**
     * Process a withdrawal request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $currency
     * @return \Illuminate\Http\RedirectResponse
     */
    public function withdraw(Request $request, $currency)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00000001',
            'address' => 'required|string',
        ]);

        $user = Auth::user();
        $wallet = $user->wallets()->where('currency', strtoupper($currency))->firstOrFail();
        
        // Check if user has enough balance
        if ($wallet->balance < $request->amount) {
            return back()->withErrors([
                'amount' => 'Insufficient balance.',
            ]);
        }
        
        // In a real app, you would initiate an actual blockchain transaction here
        // For now, we'll just update the balance in the database
        $wallet->subtractFunds($request->amount);
        
        return redirect()->route('wallets.index')
            ->with('success', "Withdrawal of {$request->amount} {$currency} initiated successfully!");
    }

    /**
     * Process a demo deposit (for testing).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $currency
     * @return \Illuminate\Http\RedirectResponse
     */
    public function demoDeposit(Request $request, $currency)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.00000001|max:1', // Limit max deposit for demo
        ]);

        $user = Auth::user();
        $wallet = $user->wallets()->where('currency', strtoupper($currency))->firstOrFail();
        
        // Add funds (for demo purposes only)
        $wallet->addFunds($request->amount);
        
        return redirect()->route('wallets.index')
            ->with('success', "Demo deposit of {$request->amount} {$currency} completed!");
    }
}
