<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asignaciones_vehiculo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios');
            $table->foreignId('vehiculo_id')
                ->constrained('vehiculos');
            
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->text('observaciones_devolucion')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_vehiculo');
    }
};
