<?php
namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Factories\HasFactory; // ← agregar
=======
use Illuminate\Database\Eloquent\Factories\HasFactory; // add this
>>>>>>> dev-frontend
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Message;

class User extends Authenticatable
{

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