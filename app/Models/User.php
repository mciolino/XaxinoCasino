<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PragmaRX\Google2FALaravel\Support\Authenticatable as Google2FAAuthenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Google2FAAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        'role',
        'kyc_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the wallets for the user.
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the dice bets for the user.
     */
    public function diceBets()
    {
        return $this->hasMany(DiceBet::class);
    }

    /**
     * Get the KYC document for the user.
     */
    public function kycDocuments()
    {
        return $this->hasMany(KycDocument::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if KYC is verified.
     */
    public function isKycVerified()
    {
        return $this->kyc_status === 'verified';
    }

    /**
     * Get the Bitcoin wallet for the user.
     */
    public function getBtcWallet()
    {
        return $this->wallets()->where('currency', 'BTC')->first();
    }

    /**
     * Get the Ethereum wallet for the user.
     */
    public function getEthWallet()
    {
        return $this->wallets()->where('currency', 'ETH')->first();
    }
}
