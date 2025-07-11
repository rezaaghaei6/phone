<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCode extends Model
{
    protected $fillable = ['phone', 'code', 'is_valid', 'expires_at', 'daily_count', 'last_sent_at'];
    protected $casts = [
        'is_valid' => 'boolean', 
        'expires_at' => 'datetime', 
        'last_sent_at' => 'datetime'
    ];

    /**
     * رابطه با مدل User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'phone', 'phone');
    }
}