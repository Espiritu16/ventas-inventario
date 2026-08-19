<?php

namespace App\Compartido\Autorizacion;

use App\Compartido\Interfaz\RegistroDeComponentesLivewire;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Routing\Route;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * Traducción a código de docs/requisitos/actores-permisos.md, que es la
 * fuente: esta clase no decide permisos, los transcribe.
 *
 * Deny-by-default (RNF-013): lo que no aparece acá se rechaza. La ausencia de
 * una entrada nunca significa "abierto", significa "todavía nadie lo
 * autorizó".
 *
 * Las entradas se escriben como método y URI —`POST /usuarios`— y no por
 * nombre de ruta, por dos razones: es la forma exacta en que la gobernanza
 * las declara, de modo que ambos documentos se comparan línea a línea; y hay
 * rutas que deben responder y no tienen nombre, empezando por `GET /up`.
 *
 * Cada sprint agrega las filas de los recursos que crea. Las que aún no
 * existen no se declaran por adelantado: una regla para una ruta inexistente
 * no protege nada y envejece mal.
 */
final class MatrizDePermisos
{
    /**
     * Tabla "Rutas de infraestructura" de la gobernanza. No exponen ningún
     * recurso de negocio, así que no tienen actor. La lista es cerrada: nada
     * se agrega acá sin agregarse antes a ese documento.
     */
    private const INFRAESTRUCTURA = [
        'GET /up',
        'GET /',
    ];

    /**
     * Sección "Rutas que registra Livewire", filas públicas. Se escriben
     * relativas al prefijo porque el prefijo no es fijo: Livewire lo deriva
     * de APP_KEY, así que cambia entre desarrollo, pruebas y producción. Se
     * resuelve en cada petición desde la misma fuente que registra las rutas,
     * de modo que la declaración no pueda quedar apuntando a una ruta que ya
     * no existe.
     */
    private const LIVEWIRE_EXTENSIONES_PUBLICAS = ['js', 'css', 'map'];

    /**
     * Sección "Rutas que registra Livewire": exige sesión activa.
     *
     * Es el canal por el que se invoca cualquier método público de cualquier
     * componente montado. El control se ejerce acá y se suma al que Livewire
     * aplica por su cuenta; no lo delega.
     */
    private const LIVEWIRE_CON_SESION = [
        'POST /update',
    ];

    /**
     * Componentes que pueden invocarse sin sesión — lista cerrada.
     *
     * Se declaran por su clase, y el nombre con que se invocan se deriva de
     * la misma fuente que los registra: si se escribiera la cadena a mano,
     * un renombre dejaría la declaración apuntando a un componente
     * inexistente sin que nada fallara, y el control se apagaría en silencio.
     *
     * El de inicio de sesión es el único previsto, y lo construye S-01-F.
     * Agregar una entrada es decisión de Arquitectura, nunca del sprint que
     * la necesita.
     *
     * @var array<int, class-string>
     */
    private const COMPONENTES_SIN_SESION = [
        'App\\Dominios\\Usuarios\\Livewire\\InicioDeSesion',
    ];

    /** Matriz de permisos, filas del actor Anónimo. */
    private const ANONIMAS = [
        'POST /login',
    ];

    /**
     * Matriz de permisos, filas con rol técnico.
     *
     * @var array<string, array<int, string>>
     */
    private const POR_ROL = [
        'POST /logout' => [Usuario::ROL_ADMINISTRADOR, Usuario::ROL_VENDEDOR],
        'GET /usuarios' => [Usuario::ROL_ADMINISTRADOR],
        'POST /usuarios' => [Usuario::ROL_ADMINISTRADOR],
        'PATCH /usuarios/{id}' => [Usuario::ROL_ADMINISTRADOR],
    ];

    public static function identificar(string $metodo, Route $ruta): string
    {
        return strtoupper($metodo).' /'.ltrim($ruta->uri(), '/');
    }

    public static function seSirveSinSesion(string $identificador): bool
    {
        return in_array($identificador, self::INFRAESTRUCTURA, true)
            || in_array($identificador, self::ANONIMAS, true)
            || self::esAssetDeLivewire($identificador);
    }

    /**
     * Asset estático de Livewire: solo GET, solo bajo el prefijo y solo con
     * las extensiones declaradas.
     *
     * Se reconoce por patrón y no por lista enumerada porque el conjunto
     * exacto de assets depende de la versión de Livewire: hoy son
     * `livewire.js` y dos mapas de origen, y una actualización del paquete
     * puede sumar o renombrar alguno. Una lista enumerada que se quedara
     * corta dejaría de cargar Livewire sin que nada más fallara.
     */
    private static function esAssetDeLivewire(string $identificador): bool
    {
        $relativo = self::relativoALivewire($identificador);

        if ($relativo === $identificador || ! str_starts_with($relativo, 'GET /')) {
            return false;
        }

        $ruta = substr($relativo, strlen('GET /'));

        return in_array(pathinfo($ruta, PATHINFO_EXTENSION), self::LIVEWIRE_EXTENSIONES_PUBLICAS, true);
    }

    /** Exige sesión, pero no un rol concreto. */
    public static function exigeSoloSesion(string $identificador): bool
    {
        return in_array(self::relativoALivewire($identificador), self::LIVEWIRE_CON_SESION, true);
    }

    public static function componentePuedeInvocarseSinSesion(string $componente): bool
    {
        $abiertos = array_map(
            fn (string $clase) => RegistroDeComponentesLivewire::nombreDe($clase),
            self::COMPONENTES_SIN_SESION
        );

        return in_array($componente, $abiertos, true);
    }

    public static function tieneReglaDeclarada(string $identificador): bool
    {
        return self::seSirveSinSesion($identificador)
            || self::exigeSoloSesion($identificador)
            || array_key_exists($identificador, self::POR_ROL);
    }

    /**
     * Devuelve el identificador sin el prefijo de Livewire, o el mismo
     * identificador si no es una ruta suya.
     */
    private static function relativoALivewire(string $identificador): string
    {
        [$metodo, $uri] = explode(' ', $identificador, 2);
        $prefijo = ltrim(EndpointResolver::prefix(), '/');

        if (! str_starts_with(ltrim($uri, '/'), $prefijo.'/')) {
            return $identificador;
        }

        return $metodo.' /'.substr(ltrim($uri, '/'), strlen($prefijo) + 1);
    }

    public static function permiteA(string $identificador, Usuario $usuario): bool
    {
        return in_array($usuario->rol, self::POR_ROL[$identificador] ?? [], true);
    }
}
