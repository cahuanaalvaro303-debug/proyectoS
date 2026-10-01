<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permiso extends Model
{
    //
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion'
    ];

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class, 'usuario_permiso', 'permiso_id', 'usuario_id'
        );
    }
}
