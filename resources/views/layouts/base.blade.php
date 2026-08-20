{{--
    Envoltorio de compatibilidad para las vistas que todavía usan
    `@extends('layouts.base')`.

    El armazón real vive una sola vez en el componente `<x-layout>`; acá no se
    repite el HTML del documento. Se conserva este archivo porque
    `tests/recursos/vistas/pagina-de-humo.blade.php`, que es de S-01-B, lo
    extiende: borrarlo rompería una prueba ajena, y este frente no escribe en
    el territorio del otro.
--}}
<x-layout :titulo="$titulo ?? null">
    @yield('contenido')
</x-layout>
