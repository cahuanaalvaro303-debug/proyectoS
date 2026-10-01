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
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios');

            $table->string('accion', 50);
            $table->string('entidad', 100);
            $table->unsignedBigInteger('entidad_id');

            $table->jsonb('valores_anteriores');
            $table->jsonb('valores_nuevos');

            $table->string('direccion_ip', 45);

            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
