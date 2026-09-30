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
        Schema::create('usuario_permiso', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->constrained('usuarios');
            $table->foreignId('permiso_id')
                ->constrained('permisos');

            $table->primary(['usuario_id', 'permiso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_permiso');
    }
};
