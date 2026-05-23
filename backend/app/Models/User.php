<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // add this
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // add HasFactory here

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}