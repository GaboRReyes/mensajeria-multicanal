<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // ← agregar
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Message;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory; // ← agregar HasFactory

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
        
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}