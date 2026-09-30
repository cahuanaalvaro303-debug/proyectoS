<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Fondation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Model
{
    //
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    
    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'email',
        'password',
        'activo'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array 
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}
