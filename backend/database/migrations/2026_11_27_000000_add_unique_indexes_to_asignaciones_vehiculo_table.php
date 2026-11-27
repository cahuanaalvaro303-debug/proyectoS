<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX asignaciones_vehiculo_usuario_activo_unique
            ON asignaciones_vehiculo (usuario_id)
            WHERE activo = true AND fecha_fin IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX asignaciones_vehiculo_vehiculo_activo_unique
            ON asignaciones_vehiculo (vehiculo_id)
            WHERE activo = true AND fecha_fin IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS asignaciones_vehiculo_usuario_activo_unique');
        DB::statement('DROP INDEX IF EXISTS asignaciones_vehiculo_vehiculo_activo_unique');
    }
};
