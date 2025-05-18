<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Public Routes
Route::get('/', function () {
    return view('home');
})->name('home');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', function() {
        return view('auth.forgot-password');
    })->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

// Auth Required Routes
Route::middleware('auth')->group(function () {
    // 2FA Routes
    Route::get('/2fa/verify', [AuthController::class, 'show2faVerificationForm'])->name('2fa.verify');
    Route::post('/2fa/verify', [AuthController::class, 'verify2fa']);
    Route::get('/2fa/setup', [AuthController::class, 'show2faSetupForm'])->name('2fa.setup');
    Route::post('/2fa/complete', [AuthController::class, 'complete2faSetup'])->name('2fa.complete');
    
    // User Profile
    Route::get('/profile', function() {
        return view('profile.index');
    })->name('profile');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Wallet Routes
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('/setup', [WalletController::class, 'setup'])->name('wallets.setup');
        Route::post('/generate', [WalletController::class, 'generate'])->name('wallets.generate');
        Route::get('/deposit/{currency}', [WalletController::class, 'showDeposit'])->name('wallets.deposit');
        Route::get('/withdraw/{currency}', [WalletController::class, 'showWithdrawal'])->name('wallets.withdraw');
        Route::post('/withdraw/{currency}', [WalletController::class, 'withdraw'])->name('wallets.doWithdraw');
        Route::post('/demo-deposit/{currency}', [WalletController::class, 'demoDeposit'])->name('wallets.demoDeposit');
    });
    
    // KYC Routes
    Route::prefix('kyc')->group(function () {
        Route::get('/', [KycController::class, 'showUploadForm'])->name('kyc.upload');
        Route::post('/', [KycController::class, 'upload']);
        Route::get('/status', [KycController::class, 'status'])->name('kyc.status');
        Route::delete('/{id}', [KycController::class, 'destroy'])->name('kyc.destroy');
    });
    
    // Game Routes
    Route::prefix('games')->group(function () {
        Route::get('/dice', [GameController::class, 'showDice'])->name('games.dice');
        Route::post('/dice/play', [GameController::class, 'playDice'])->name('games.dice.play');
        Route::post('/dice/verify', [GameController::class, 'verifyDice'])->name('games.dice.verify');
        Route::get('/dice/recent-bets', [GameController::class, 'getRecentBets'])->name('games.dice.recentBets');
    });
    
    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/kyc', [AdminController::class, 'showKycVerification'])->name('admin.kyc');
        Route::post('/kyc/{id}/approve', [AdminController::class, 'approveKyc'])->name('admin.kyc.approve');
        Route::post('/kyc/{id}/reject', [AdminController::class, 'rejectKyc'])->name('admin.kyc.reject');
        Route::get('/users', [AdminController::class, 'showUserManagement'])->name('admin.users');
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser'])->name('admin.users.ban');
        Route::post('/users/{id}/unban', [AdminController::class, 'unbanUser'])->name('admin.users.unban');
    });
});

// Admin middleware definition in app/Http/Kernel.php:
// 'admin' => \App\Http\Middleware\AdminMiddleware::class,

// Create Admin middleware
Route::get('/create-admin-middleware', function() {
    $path = app_path('Http/Middleware/AdminMiddleware.php');
    
    if (!file_exists($path)) {
        $middleware = <<<'EOT'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect('/')->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
EOT;
        
        file_put_contents($path, $middleware);
        
        return "AdminMiddleware created successfully! Add to Kernel.php: 'admin' => \App\Http\Middleware\AdminMiddleware::class";
    }
    
    return "AdminMiddleware already exists!";
});
