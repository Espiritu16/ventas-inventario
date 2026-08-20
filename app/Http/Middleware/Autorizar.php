<?php

namespace App\Http\Middleware;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Compartido\Autorizacion\PeticionDeLivewire;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Control de acceso deny-by-default (RNF-013).
 *
 * Se aplica a todas las rutas, no se elige ruta por ruta: una regla que hay
 * que acordarse de poner protege solo lo que alguien recordó. Una ruta sin
 * fila en la matriz se rechaza aunque quien la pida sea administrador —
 * agregar una ruta y olvidar su permiso debe fallar de forma visible, no
 * quedar abierta.
 */
class Autorizar
{
    public function handle(Request $peticion, Closure $siguiente): Response
    {
        $ruta = $peticion->route() ?? $this->resolver($peticion);

        if ($ruta === null) {
            return $siguiente($peticion);
        }

        $identificador = MatrizDePermisos::identificar($peticion->method(), $ruta);

        if (MatrizDePermisos::seSirveSinSesion($identificador)) {
            return $siguiente($peticion);
        }

        $usuario = Auth::user();
        $tieneSesion = $usuario !== null && $usuario->activo;

        // El endpoint por el que viajan las interacciones de Livewire exige
        // sesión. Sin ella solo pasan los componentes que la gobernanza
        // declara accesibles sin sesión, en lista cerrada: si se dejara
        // abierto, el control de acceso pasaría a depender de lo que el
        // paquete haga por su cuenta.
        if (MatrizDePermisos::exigeSoloSesion($identificador)) {
            if ($tieneSesion || $this->soloInvocaComponentesAbiertos($peticion)) {
                return $siguiente($peticion);
            }

            throw new ErrorDeDominio(
                CodigoDeError::NO_AUTENTICADO,
                'Necesitas iniciar sesión para continuar.'
            );
        }

        // Un usuario desactivado mientras tenía la sesión abierta deja de
        // tener acceso en la petición siguiente: si solo se comprobara al
        // iniciar sesión, desactivar a alguien no lo sacaría del sistema.
        if (! $tieneSesion) {
            return $this->rechazarPorFaltaDeSesion($peticion);
        }

        if (! MatrizDePermisos::tieneReglaDeclarada($identificador)
            || ! MatrizDePermisos::permiteA($identificador, $usuario)) {
            throw new ErrorDeDominio(
                CodigoDeError::NO_AUTORIZADO,
                'Tu rol no tiene permiso para esta operación.'
            );
        }

        return $siguiente($peticion);
    }

    /**
     * Resuelve a mano la ruta cuando el enrutador todavía no lo hizo.
     *
     * Este middleware corre como global y no dentro de un grupo, porque un
     * grupo solo alcanza a las rutas que lo declaran: `/up`, los assets de
     * Livewire y `/storage/{path}` quedan fuera de `web` y, con el control
     * puesto en el grupo, respondían por no estar alcanzadas en vez de por
     * estar declaradas. Justamente lo que deny-by-default debe impedir.
     *
     * Sin ruta que resolver —404 o método no permitido— responde el
     * framework: no hay nada que autorizar.
     */
    private function resolver(Request $peticion): ?Route
    {
        try {
            return app(Router::class)->getRoutes()->match($peticion);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Quien navegaba a una pantalla vuelve a ella después de identificarse;
     * quien consumía datos recibe el rechazo de la taxonomía.
     *
     * La diferencia no es cosmética. Un 401 de texto plano deja al navegador
     * en un callejón sin salida: la persona pidió una pantalla y recibe un
     * mensaje suelto, sin forma de llegar al acceso ni de volver a donde iba.
     * Y a Livewire hay que darle el 401 igual, porque un redirect en respuesta
     * a una interacción de componente no sabría manejarlo.
     */
    private function rechazarPorFaltaDeSesion(Request $peticion): Response
    {
        if ($peticion->expectsJson() || $this->esDeLivewire($peticion)) {
            throw new ErrorDeDominio(
                CodigoDeError::NO_AUTENTICADO,
                'Necesitas iniciar sesión para continuar.'
            );
        }

        // Se usa el almacén de sesión del contenedor y no `$peticion->session()`:
        // este middleware es global y la petición todavía no tiene el almacén
        // asignado, aunque la sesión en sí ya esté disponible.
        //
        // Y se guarda a mano en vez de con `redirect()->guest()`, que solo la
        // guarda si el enrutador ya resolvió la ruta: con `guest()` la persona
        // volvía siempre a la raíz en lugar de a lo que había pedido.
        session()->put(
            'url.intended',
            $peticion->isMethod('GET') ? $peticion->fullUrl() : url()->previous()
        );

        return redirect('/login');
    }

    private function esDeLivewire(Request $peticion): bool
    {
        return $peticion->hasHeader('X-Livewire')
            || str_starts_with('/'.ltrim($peticion->path(), '/'), EndpointResolver::prefix().'/');
    }

    private function soloInvocaComponentesAbiertos(Request $peticion): bool
    {
        $componentes = PeticionDeLivewire::componentes($peticion);

        if ($componentes === null) {
            return false;
        }

        foreach ($componentes as $componente) {
            if (! MatrizDePermisos::componentePuedeInvocarseSinSesion($componente)) {
                return false;
            }
        }

        return true;
    }
}
