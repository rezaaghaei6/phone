<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['phone', 'name', 'surname', 'is_admin'];
    protected $casts = ['is_admin' => 'boolean'];
}