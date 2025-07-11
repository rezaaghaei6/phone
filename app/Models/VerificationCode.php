<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $fillable = ['phone', 'code', 'is_valid', 'expires_at', 'daily_count', 'last_sent_at'];
    protected $casts = ['is_valid' => 'boolean', 'expires_at' => 'datetime', 'last_sent_at' => 'datetime'];
}