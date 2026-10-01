<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios');

            $table->foreignId('vehiculo_id')
                ->constrained('vehiculos');

            $table->decimal('velocidad', 8, 2);
            $table->decimal('direccion', 6, 2);
            $table->decimal('precision', 8, 2);

            $table->timestamp('registrado_en');
            $table->timestamp('recibido_en');
        });
        DB::statement("
            ALTER TABLE ubicaciones
            ADD COLUMN ubicacion geography(Point, 4326)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
