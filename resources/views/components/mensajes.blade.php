@props(['error' => null, 'exito' => null])

{{--
    Avisos de la operación: el error de negocio y la confirmación del guardado.
    Vive acá porque toda pantalla de gestión los muestra igual, y cuatro copias
    del mismo bloque se separan en cuanto una cambie.

    Los errores de validación NO van acá: esos se muestran junto al campo que
    los produjo.
--}}
@if ($error)
    <x-aviso tipo="error">{{ $error }}</x-aviso>
@endif

@if ($exito)
    <x-aviso tipo="exito">{{ $exito }}</x-aviso>
@endif
