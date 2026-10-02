<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BitacoraAuditoria extends Model
{
    //
    use HasFactory;

    protected $table = 'bitacora_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad',
        'entidad_id',
        'valores_anteriores',
        'valores_nuevos',
        'direccion_ip',
        'created_at'
    ];

    protected function casts(): array {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'created_at' => 'datetime'
        ];
    }

    public function usuario() {
        return $this->belongsTo(
            Usuario::class, 'usuario_id'
        );
    }
}
