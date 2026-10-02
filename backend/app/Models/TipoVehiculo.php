<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoVehiculo extends Model
{
    //
    use HasFactory;

    protected $table = 'tipos_vehiculo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean'
        ];
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'tipo_vehiculo_id');
    }

}
