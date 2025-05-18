@extends('layouts.app')

@section('content')
<div class="casino-hero">
    <div class="hero-content text-center mb-5">
        <h1 class="display-4 fw-bold">Welcome to Xaxino Casino</h1>
        <p class="lead">The Ultimate Blockchain Casino Experience with Provably Fair Games</p>
        @guest
        <div class="mt-4">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg me-2">Sign Up</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
        </div>
        @else
        <div class="mt-4">
            <a href="{{ route('games.dice') }}" class="btn btn-primary btn-lg me-2">Play Dice Game</a>
            <a href="{{ route('wallets.index') }}" class="btn btn-outline-light btn-lg">My Wallets</a>
        </div>
        @endguest
    </div>

    <div class="container">
        <div class="row features">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-dice fa-3x"></i>
                    </div>
                    <h3>Provably Fair Games</h3>
                    <p>Verify the fairness of every bet with our transparent provably fair algorithm.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-wallet fa-3x"></i>
                    </div>
                    <h3>Crypto Wallets</h3>
                    <p>Generate BTC and ETH wallets directly on our platform for secure gaming.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-3x"></i>
                    </div>
                    <h3>Secure 2FA</h3>
                    <p>Protect your account with Google Authenticator two-factor authentication.</p>
                </div>
            </div>
        </div>

        <div class="section-divider my-5"></div>

        <div class="games-section mb-5">
            <h2 class="text-center mb-4">Our Games</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="game-card">
                        <div class="game-image dice-game-img">
                            <div class="game-overlay">
                                <a href="{{ route('games.dice') }}" class="btn btn-primary">Play Now</a>
                            </div>
                        </div>
                        <div class="game-info p-3">
                            <h4>Dice Game</h4>
                            <p>Roll the dice and win up to 2x your bet! Provably fair algorithm ensures transparency.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="game-card coming-soon">
                        <div class="game-image slots-game-img">
                            <div class="game-overlay">
                                <span class="coming-soon-badge">Coming Soon</span>
                            </div>
                        </div>
                        <div class="game-info p-3">
                            <h4>Slots</h4>
                            <p>Classic slot machine experience with crypto payouts! Multiple paylines and bonus features.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-divider my-5"></div>

        <div class="crypto-section mb-5">
            <h2 class="text-center mb-4">Supported Cryptocurrencies</h2>
            <div class="row justify-content-center">
                <div class="col-md-5 mb-4">
                    <div class="crypto-card">
                        <div class="crypto-logo">
                            <i class="fab fa-bitcoin fa-4x"></i>
                        </div>
                        <div class="crypto-info">
                            <h4>Bitcoin (BTC)</h4>
                            <p>The original cryptocurrency that started it all. Fast and secure transactions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 mb-4">
                    <div class="crypto-card">
                        <div class="crypto-logo">
                            <i class="fab fa-ethereum fa-4x"></i>
                        </div>
                        <div class="crypto-info">
                            <h4>Ethereum (ETH)</h4>
                            <p>Smart contract platform enabling on-chain payouts and transparent gaming.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
