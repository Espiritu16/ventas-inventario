<?php

namespace App\Dominios\Usuarios\Controllers;

use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Inicio y cierre de sesión (RF-001, contrato docs/contratos/usuarios.md v1).
 *
 * La validación y el rechazo viven en el servicio, no acá: los componentes
 * Livewire llaman al servicio directamente y tienen que obtener exactamente
 * el mismo comportamiento que una petición HTTP.
 */
class SesionController
{
    public function __construct(private readonly UsuarioService $usuarios) {}

    public function iniciar(Request $peticion): JsonResponse|RedirectResponse
    {
        $usuario = $this->usuarios->autenticar(
            (string) $peticion->input('email'),
            (string) $peticion->input('password'),
        );

        Auth::login($usuario);

        // La sesión se regenera al autenticar: si se conservara el
        // identificador anterior, una sesión preexistente quedaría elevada a
        // la del usuario que acaba de entrar.
        $peticion->session()->regenerate();

        return $peticion->expectsJson()
            ? response()->json(['redirigir' => '/panel'])
            : redirect('/panel');
    }

    public function cerrar(Request $peticion): JsonResponse|RedirectResponse
    {
        Auth::logout();

        $peticion->session()->invalidate();
        $peticion->session()->regenerateToken();

        return $peticion->expectsJson()
            ? response()->json(['redirigir' => '/login'])
            : redirect('/login');
    }
}
