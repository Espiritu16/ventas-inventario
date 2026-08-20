<?php

namespace App\Compartido\Autorizacion;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Interfaz\RegistroDeComponentesLivewire;
use Illuminate\Support\Facades\Auth;
use Livewire\ComponentHook;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;

/**
 * Exige que todo componente declare qué permiso necesita, y lo comprueba cada
 * vez que sirve o escribe datos.
 *
 * Proteger la ruta de una pantalla no protege sus componentes: los métodos
 * públicos viajan por el endpoint de actualización de Livewire, que exige
 * sesión pero no distingue rol. Y comprobar al montar tampoco alcanza — en
 * cada interacción el componente se hidrata desde el snapshot que el navegador
 * guardó, y `render()` vuelve a consultar sin pasar por el montaje.
 *
 * Es un hook global y no una clase base a propósito: una clase base solo
 * protege a quien se acordó de extenderla, o sea justo en el caso en que ya se
 * había acordado. Con el hook, el que no declara falla.
 */
class HookDePermisos extends ComponentHook
{
    public function render($view, $data)
    {
        $this->exigir($this->declaradoEnLaClase());

        return null;
    }

    public function call($metodo, $parametros, $devolverTemprano)
    {
        // Un método sin atributo propio hereda el de la pantalla: si no podés
        // ver la pantalla, tampoco abrir su modal. No hay atributo de escape,
        // porque uno pensado para métodos que no tocan datos termina puesto en
        // alguno que sí, y desde afuera se parecen.
        $this->exigir($this->declaradoEnElMetodo($metodo) ?? $this->declaradoEnLaClase());

        return null;
    }

    private function declaradoEnLaClase(): ?string
    {
        $atributos = (new ReflectionClass($this->component))->getAttributes(Permiso::class);

        return $atributos === [] ? null : $atributos[0]->newInstance()->identificador;
    }

    private function declaradoEnElMetodo(string $metodo): ?string
    {
        if (! method_exists($this->component, $metodo)) {
            return null;
        }

        $atributos = (new ReflectionMethod($this->component, $metodo))->getAttributes(Permiso::class);

        return $atributos === [] ? null : $atributos[0]->newInstance()->identificador;
    }

    private function exigir(?string $identificador): void
    {
        // No declarar es un defecto de programación, no una situación de uso.
        // Hacia afuera se responde igual que un rechazo por rol —distinguirlos
        // filtraría información— pero el detalle lo separa, porque buscar el
        // problema en la matriz cuando lo que falta es el atributo es perder
        // el tiempo donde no está.
        if ($identificador === null) {
            throw $this->rechazo('componente_sin_permiso_declarado');
        }

        if (MatrizDePermisos::seSirveSinSesion($identificador)) {
            return;
        }

        $usuario = Auth::user();

        if ($usuario === null || ! $usuario->activo || ! MatrizDePermisos::permiteA($identificador, $usuario)) {
            throw $this->rechazo('rol_sin_permiso');
        }
    }

    private function rechazo(string $motivo): ErrorDeDominio
    {
        return new ErrorDeDominio(
            CodigoDeError::NO_AUTORIZADO,
            'Tu rol no tiene permiso para esta operación.',
            [
                'motivo' => $motivo,
                'componente' => RegistroDeComponentesLivewire::nombreDe($this->component::class),
            ]
        );
    }
}
