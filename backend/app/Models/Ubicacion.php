<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ubicacion extends Model
{
    //
    use HasFactory;

    protected $table = 'ubicaciones';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'vehiculo_id',
        'ubicacion',
        'velocidad',
        'direccion',
        'precision',
        'registrado_en',
        'recibido_en'
    ];

    protected function casts(): array {
        return [
            'velocidad' => 'decimal:2',
            'direccion' => 'decimal:2',
            'precision' => 'decimal:2',
            'registrado_en' => 'datetime',
            'recibido_en' => 'datetime'
        ];
    }

    public function usuario() {
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
