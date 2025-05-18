<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'currency',
        'address',
        'private_key',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'private_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:8',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add funds to wallet.
     *
     * @param float $amount
     * @return bool
     */
    public function addFunds($amount)
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * Subtract funds from wallet.
     *
     * @param float $amount
     * @return bool
     */
    public function subtractFunds($amount)
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            return $this->save();
        }
        return false;
    }

    /**
     * Format balance with currency symbol.
     *
     * @return string
     */
    public function formattedBalance()
    {
        $symbols = [
            'BTC' => '₿',
            'ETH' => 'Ξ',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency;
        return $symbol . ' ' . number_format($this->balance, 8);
    }
}
