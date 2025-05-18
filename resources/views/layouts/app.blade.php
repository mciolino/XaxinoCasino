<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Xaxino Casino') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="/css/casino.css" rel="stylesheet">
    
    <!-- Google Analytics -->
    @if(env('GA_TRACKING_ID'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_TRACKING_ID') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ env('GA_TRACKING_ID') }}');
    </script>
    @endif
</head>
<body class="casino-body">
    <div class="casino-container">
        <header class="casino-header">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <i class="fas fa-dice me-2"></i>{{ config('app.name', 'Xaxino Casino') }}
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('home') }}">Home</a>
                            </li>
                            @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('games.dice') }}">Dice Game</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('wallets.index') }}">My Wallets</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('kyc.upload') }}">KYC Verification</a>
                            </li>
                            @if(Auth::user()->isAdmin())
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Admin
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.kyc') }}">KYC Verification</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.users') }}">User Management</a></li>
                                </ul>
                            </li>
                            @endif
                            @endauth
                        </ul>
                        <ul class="navbar-nav">
                            @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Register</a>
                            </li>
                            @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="{{ route('profile') }}">My Profile</a></li>
                                    @if(!Auth::user()->google2fa_secret)
                                    <li><a class="dropdown-item" href="{{ route('2fa.setup') }}">Setup 2FA</a></li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}" 
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <main class="casino-content py-4">
            <div class="container">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="casino-footer text-center py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h5>Xaxino Casino</h5>
                        <p>The Ultimate Blockchain Casino Experience</p>
                    </div>
                    <div class="col-md-4">
                        <h5>Links</h5>
                        <ul class="list-unstyled">
                            <li><a href="{{ route('home') }}">Home</a></li>
                            <li><a href="#">Terms of Service</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Responsible Gaming</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5>Supported Cryptocurrencies</h5>
                        <div class="crypto-icons">
                            <i class="fab fa-bitcoin fa-2x mx-2" title="Bitcoin"></i>
                            <i class="fab fa-ethereum fa-2x mx-2" title="Ethereum"></i>
                        </div>
                    </div>
                </div>
                <hr>
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Xaxino Casino') }}. All rights reserved.</p>
                <p class="small">Provably fair gaming. Play responsibly.</p>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/app.js"></script>
    
    @stack('scripts')
</body>
</html>
