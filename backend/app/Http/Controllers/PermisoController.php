<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permiso;

class PermisoController extends Controller
{
    //
    public function listaPermisos() {
        $permisos = Permiso::all();

        return response()->json($permisos);
    }
}
