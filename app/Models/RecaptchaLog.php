<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecaptchaLog extends Model
{
    protected $fillable = ['phone', 'ip_address', 'recaptcha_token', 'recaptcha_score'];
}