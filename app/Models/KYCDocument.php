<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'document_type',
        'document_path',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get the user that owns the KYC document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the document is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->status === 'verified';
    }

    /**
     * Determine if the document is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Determine if the document is rejected.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Get the document type display name.
     *
     * @return string
     */
    public function getDocumentTypeDisplay()
    {
        $types = [
            'id_card' => 'ID Card',
            'passport' => 'Passport',
            'driving_license' => 'Driving License',
            'utility_bill' => 'Utility Bill',
        ];

        return $types[$this->document_type] ?? $this->document_type;
    }
}
