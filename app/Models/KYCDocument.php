<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KYCDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'document_type',
        'document_path',
        'document_number',
        'status',
        'rejection_reason',
    ];

    /**
     * Get the user that uploaded the document
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
