<?php

namespace App\Dominios\Catalogo\Controllers;

use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Servicios\ProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductoController
{
    /**
     * El contrato nombra los campos en el estilo del cliente y la base los
     * guarda en el suyo; la traducción vive acá para que el servicio hable un
     * solo idioma.
     */
    private const NOMBRES = [
        'categoriaId' => 'categoria_id',
        'unidadMedida' => 'unidad_medida',
        'precioMenor' => 'precio_menor',
        'precioMayor' => 'precio_mayor',
        'stockMinimo' => 'stock_minimo',
    ];

    public function __construct(private readonly ProductoService $productos) {}

    public function listar(Request $peticion): JsonResponse
    {
        $pagina = $this->productos->listar(
            buscar: $peticion->query('buscar') !== null ? (string) $peticion->query('buscar') : null,
            categoriaId: $peticion->query('categoriaId') !== null ? (int) $peticion->query('categoriaId') : null,
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

    public function ver(int $id): JsonResponse
    {
        return response()->json($this->productos->encontrar($id));
    }

    public function crear(Request $peticion): JsonResponse
    {
        return response()->json(
            $this->productos->crear(DatosDeCatalogo::desde($this->traducir($peticion))),
            201
        );
    }

    public function actualizar(Request $peticion, int $id): JsonResponse
    {
        return response()->json($this->productos->actualizar(
            $id,
            DatosDeCatalogo::desde($this->traducir($peticion)),
            Auth::user(),
        ));
    }

    /** @return array<string, mixed> */
    private function traducir(Request $peticion): array
    {
        $entrada = $peticion->only(array_merge(
            ['codigo', 'nombre', 'activo'],
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
