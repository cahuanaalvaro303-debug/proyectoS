<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AsignacionVehiculo extends Model
{
    //
    use HasFactory;

    protected $table = 'asignaciones_vehiculo';

    protected $fillable = [
        'usuario_id',
        'vehiculo_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones_entrega',
        'observaciones_devolucion',
        'activo'
    ];

    protected function casts(): array 
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'activo' => 'boolean'
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class, 'usuario_id'
        );
    }

    public function vehiculo() {
        return $this->belongsTo(
            Vehiculo::class, 'vehiculo_id'
        );
    }
}
