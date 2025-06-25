
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotBet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'bet_amount',
        'client_seed',
        'server_seed',
        'server_seed_hash',
        'result_symbols',
        'multiplier',
        'payout',
        'currency',
    ];

    /**
     * Get the user that owns the bet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this bet was a win
     */
    public function isWin()
    {
        return $this->payout > 0;
    }
}
