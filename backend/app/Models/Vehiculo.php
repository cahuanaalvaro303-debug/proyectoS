<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;

class Vehiculo extends Model implements Auditable
{
    //
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'tipo_vehiculo_id',
        'activo'
    ];

    protected function casts(): array 
    {
        return [
            'anio' => 'integer',
            'activo' => 'boolean'
        ];
    }

    public function tipoVehiculo() {
        return $this->belongsTo(
            TipoVehiculo::class, 'tipo_vehiculo_id'
        );
    }

    public function asignaciones() {
        return $this->hasMany(
            AsignacionVehiculo::class, 'vehiculo_id'
        );
    }

    public function ubicaciones() {
        return $this->hasMany(
            Ubicacion::class, 'vehiculo_id'
        );
    }
}
