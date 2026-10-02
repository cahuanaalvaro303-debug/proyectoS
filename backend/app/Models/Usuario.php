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

    public function permisos()
    {
        return $this->belongsToMany(
            Permiso::class, 'usuario_permiso', 'usuario_id', 'permiso_id'
        );
    }

    public function asignaciones() {
        return $this->hasMany(
            AsignacionVehiculo::class, 'usuario_id'
        );
    }
    
    public function ubicaciones() {
        return $this->hasMany(
            Ubicacion::class, 'usuario_id'
        );
    }

    public function auditorias() {
        return $this->hasMany(
            BitacoraAuditoria::class, 'usuario_id'
        );
    }
}
