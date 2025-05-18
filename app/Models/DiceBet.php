<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiceBet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bet_amount',
        'client_seed',
        'server_seed',
        'server_seed_hash',
        'rolled_number',
        'payout',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bet_amount' => 'decimal:8',
        'rolled_number' => 'decimal:2',
        'payout' => 'decimal:8',
    ];

    /**
     * Get the user that owns the bet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the bet was a win.
     *
     * @return bool
     */
    public function isWin()
    {
        return $this->payout > $this->bet_amount;
    }

    /**
     * Verify the fairness of the roll.
     *
     * @return bool
     */
    public function verifyFairness()
    {
        $combinedSeed = $this->server_seed . $this->client_seed;
        $hash = hash('sha256', $combinedSeed);
        $number = hexdec(substr($hash, 0, 8)) % 10000 / 100;
        
        return abs($number - $this->rolled_number) < 0.01; // Allow for minor rounding differences
    }

    /**
     * Calculate the appropriate payout based on the roll and bet amount.
     *
     * @param float $multiplier
     * @return float
     */
    public function calculatePayout($multiplier)
    {
        return $this->bet_amount * $multiplier;
    }
}
