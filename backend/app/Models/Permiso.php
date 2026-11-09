<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class Permiso extends Model implements Auditable
{
    //
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

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
