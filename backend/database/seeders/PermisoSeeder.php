<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Permiso;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
            $permisos = [
            [
                'nombre' => 'Dashboard',
                'codigo' => 'dashboard',
                'descripcion' => 'Permite acceder al dashboard del sistema.',
            ],
            [
                'nombre' => 'Gestión de usuarios',
                'codigo' => 'usuarios',
                'descripcion' => 'Permite gestionar usuarios y sus permisos.',
            ],
            [
                'nombre' => 'Gestión de vehículos',
                'codigo' => 'vehiculos',
                'descripcion' => 'Permite gestionar vehículos.',
            ],
            [
                'nombre' => 'Gestión de tipos de vehículos',
                'codigo' => 'tipos_vehiculo',
                'descripcion' => 'Permite gestionar los tipos de vehículos.',
            ],
            [
                'nombre' => 'Gestión de asignaciones',
                'codigo' => 'asignaciones',
                'descripcion' => 'Permite gestionar las asignaciones de vehículos.',
            ],
            [
                'nombre' => 'Monitoreo de ubicaciones',
                'codigo' => 'ubicaciones',
                'descripcion' => 'Permite consultar y monitorear las ubicaciones actuales.',
            ],
            [
                'nombre' => 'Historial de ubicaciones',
                'codigo' => 'historial_ubicaciones',
                'descripcion' => 'Permite consultar el historial y recorridos de ubicaciones.',
            ],
            [
                'nombre' => 'Bitácora de auditoría',
                'codigo' => 'auditoria',
                'descripcion' => 'Permite consultar la bitácora de auditoría.',
            ],
            [
                'nombre' => 'Enviar ubicación',
                'codigo' => 'enviar_ubicacion',
                'descripcion' => 'Permite enviar ubicaciones desde la aplicación móvil.',
            ],
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['codigo' => $permiso['codigo']],
                $permiso
            );
        }

        $usuario = Usuario::find(1);

        if ($usuario) {
            $usuario->permisos()->sync(
                Permiso::pluck('id')
            );
        }
    }
}
