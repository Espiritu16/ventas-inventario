<?php

use App\Compartido\Errores\ErrorDeDominio;
use App\Http\Middleware\Autorizar;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/backend.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, no dentro del grupo `web`: un grupo solo alcanza a las
        // rutas que lo declaran, y `/up`, los assets de Livewire y
        // `/storage/{path}` no lo declaran. Con el control en el grupo, esas
        // rutas respondían por quedar fuera del alcance en vez de por estar
        // declaradas — que es exactamente lo que deny-by-default impide.
        // RNF-013.
        $middleware->append(Autorizar::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Un rechazo previsto por el contrato no es una falla del sistema: se
        // responde con su código de la taxonomía y su status, sin traza ni
        // nada interno (RNF-014).
        $exceptions->render(function (ErrorDeDominio $error, Request $peticion) {
            if ($peticion->expectsJson()) {
                return response()->json($error->comoRespuesta(), $error->status());
            }

            return response($error->getMessage(), $error->status());
        });

        // Un rechazo del contrato no se reporta como error interno.
        $exceptions->dontReport(ErrorDeDominio::class);
    })->create();
