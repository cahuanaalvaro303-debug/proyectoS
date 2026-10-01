<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            CREATE INDEX ubicaciones_ubicacion_gist
            ON ubicaciones
            USING GIST (ubicacion)
        ');
    }

    public function down(): void
    {
        DB::statement('
            DROP INDEX IF EXISTS ubicaciones_ubicacion_gist
        ');
    }

};
