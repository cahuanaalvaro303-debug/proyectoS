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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();

            $table->string('placa', 20)->unique();
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->integer('anio');
            $table->string('color', 50);
            $table->foreignId('tipo_vehiculo_id')
                ->constrained('tipos_vehiculo');

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
