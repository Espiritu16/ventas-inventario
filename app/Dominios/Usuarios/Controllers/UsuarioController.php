<?php

namespace App\Dominios\Usuarios\Controllers;

use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Alta, listado y edición de usuarios (RF-002, contrato v1).
 */
class UsuarioController
{
    public function __construct(private readonly UsuarioService $usuarios) {}

    public function listar(Request $peticion): JsonResponse
    {
        $pagina = $this->usuarios->listar(
            $peticion->query('buscar') !== null ? (string) $peticion->query('buscar') : null,
            (int) $peticion->query('pagina', 1),
        );

        return response()->json([
            'datos' => $pagina->items(),
            'pagina' => $pagina->currentPage(),
            'por_pagina' => $pagina->perPage(),
            'total' => $pagina->total(),
        ]);
    }

    public function crear(Request $peticion): JsonResponse|RedirectResponse
    {
        $usuario = $this->usuarios->crear(DatosUsuario::desde(
            $peticion->only(['nombre', 'email', 'password', 'rol'])
        ));

        return $peticion->expectsJson()
            ? response()->json($usuario, 201)
            : redirect('/usuarios');
    }

    public function actualizar(Request $peticion, int $id): JsonResponse|RedirectResponse
    {
        $usuario = $this->usuarios->actualizar(
            $id,
            DatosUsuario::desde($peticion->only(['nombre', 'email', 'rol', 'activo'])),
        );

        return $peticion->expectsJson()
            ? response()->json($usuario)
            : redirect('/usuarios');
    }
}
