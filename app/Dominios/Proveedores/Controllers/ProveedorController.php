<?php

namespace App\Dominios\Proveedores\Controllers;

use App\Compartido\Datos\DatosDeEntrada;
use App\Dominios\Proveedores\Servicios\ProveedorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorController
{
    private const NOMBRES = [
        'numeroDocumento' => 'numero_documento',
        'razonSocial' => 'razon_social',
    ];

    public function __construct(private readonly ProveedorService $proveedores) {}

    public function listar(Request $peticion): JsonResponse
    {
        $pagina = $this->proveedores->listar(
            buscar: $peticion->query('buscar') !== null ? (string) $peticion->query('buscar') : null,
            soloActivos: filter_var($peticion->query('soloActivos', 'true'), FILTER_VALIDATE_BOOLEAN),
            pagina: (int) $peticion->query('pagina', 1),
        );

        return response()->json([
            'datos' => $pagina->items(),
            'pagina' => $pagina->currentPage(),
            'por_pagina' => $pagina->perPage(),
            'total' => $pagina->total(),
        ]);
    }

    public function crear(Request $peticion): JsonResponse
    {
        return response()->json(
            $this->proveedores->crear(DatosDeEntrada::desde($this->traducir($peticion))),
            201
        );
    }

    public function actualizar(Request $peticion, int $id): JsonResponse
    {
        return response()->json(
            $this->proveedores->actualizar($id, DatosDeEntrada::desde($this->traducir($peticion)))
        );
    }

    /** @return array<string, mixed> */
    private function traducir(Request $peticion): array
    {
        $entrada = $peticion->only(array_merge(
            ['direccion', 'telefono', 'email', 'activo'],
            array_keys(self::NOMBRES),
        ));

        foreach (self::NOMBRES as $delContrato => $interno) {
            if (array_key_exists($delContrato, $entrada)) {
                $entrada[$interno] = $entrada[$delContrato];
                unset($entrada[$delContrato]);
            }
        }

        return $entrada;
    }
}
