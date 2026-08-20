<?php

namespace App\Compartido\Autorizacion;

use Attribute;

/**
 * Declara qué permiso de la matriz exige un componente Livewire, o uno de sus
 * métodos cuando difiere del de la pantalla.
 *
 * El identificador es la misma cadena que figura en
 * `docs/requisitos/actores-permisos.md` y en `MatrizDePermisos`: `GET /usuarios`,
 * `POST /clientes`, `PATCH /usuarios/{id}`. Una sola forma de nombrar un
 * permiso, para que el componente y la matriz no puedan decir cosas distintas.
 *
 * **No puede expresar acceso sin sesión.** Qué componentes se invocan sin
 * sesión lo declara únicamente la lista cerrada de la matriz, que aprueba
 * Arquitectura: si el atributo pudiera decirlo, cualquier sprint se
 * autoconcedería acceso anónimo escribiendo una línea en su propio componente,
 * que es exactamente lo que esa lista existe para impedir.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Permiso
{
    public function __construct(public readonly string $identificador) {}
}
