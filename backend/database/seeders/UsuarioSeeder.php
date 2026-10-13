<?php

namespace Database\Seeders;


use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::firstOrCreate(
            ['email' => 'admin@proyecto.com'],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'ci' => '00000001',
                'telefono' => '70000000',
                'password' => '12345678',
                'activo' => true,
            ]
        );
    }
}
