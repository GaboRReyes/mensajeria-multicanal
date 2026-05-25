<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Channel extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'type', 'is_active'];


    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}