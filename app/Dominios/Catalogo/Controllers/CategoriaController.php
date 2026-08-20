<?php

namespace App\Dominios\Catalogo\Controllers;

use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Servicios\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController
{
    public function __construct(private readonly CategoriaService $categorias) {}

    public function listar(Request $peticion): JsonResponse
    {
        return response()->json([
            'datos' => $this->categorias->listar(
                filter_var($peticion->query('incluirInactivas', 'false'), FILTER_VALIDATE_BOOLEAN)
            ),
        ]);
    }

    public function crear(Request $peticion): JsonResponse
    {
        return response()->json(
            $this->categorias->crear(DatosDeCatalogo::desde($peticion->only(['nombre', 'descripcion']))),
            201
        );
    }

    public function actualizar(Request $peticion, int $id): JsonResponse
    {
        return response()->json($this->categorias->actualizar(
            $id,
            DatosDeCatalogo::desde($peticion->only(['nombre', 'descripcion', 'activo'])),
        ));
    }
}
