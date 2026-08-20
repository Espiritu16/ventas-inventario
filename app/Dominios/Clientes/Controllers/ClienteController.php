<?php

namespace App\Dominios\Clientes\Controllers;

use App\Compartido\Datos\DatosDeEntrada;
use App\Dominios\Clientes\Servicios\ClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController
{
    private const NOMBRES = [
        'tipoDocumento' => 'tipo_documento',
        'numeroDocumento' => 'numero_documento',
    ];

    public function __construct(private readonly ClienteService $clientes) {}

    public function listar(Request $peticion): JsonResponse
    {
        $pagina = $this->clientes->listar(
            buscar: $peticion->query('buscar') !== null ? (string) $peticion->query('buscar') : null,
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
            $this->clientes->crear(DatosDeEntrada::desde($this->traducir($peticion))),
            201
        );
    }

    public function actualizar(Request $peticion, int $id): JsonResponse
    {
        return response()->json(
            $this->clientes->actualizar($id, DatosDeEntrada::desde($this->traducir($peticion)))
        );
    }

    /** @return array<string, mixed> */
    private function traducir(Request $peticion): array
    {
        $entrada = $peticion->only(array_merge(
            ['nombre', 'direccion', 'telefono', 'email', 'activo'],
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
