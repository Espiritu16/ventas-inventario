@props(['tipo' => 'info', 'titulo' => null])

{{--
    Aviso de una operación: el error de negocio, la confirmación de un guardado
    o una advertencia.

    Muestra el mensaje en español de la taxonomía y nunca el código
    (docs/errores/manejo-errores.md): el código se registra, no se le enseña a
    quien opera la caja. Los errores de validación no van acá — esos se
    muestran junto al campo que los produjo.
--}}
@php
    $estilos = [
        'error' => 'border-red-300 bg-red-50 text-red-900',
        'exito' => 'border-emerald-300 bg-emerald-50 text-emerald-900',
        'advertencia' => 'border-amber-300 bg-amber-50 text-amber-900',
        'info' => 'border-slate-300 bg-slate-50 text-slate-900',
    ];

    $rol = $tipo === 'error' ? 'alert' : 'status';
@endphp

<div
    role="{{ $rol }}"
    data-prueba="aviso"
    data-tipo="{{ $tipo }}"
    {{ $attributes->merge(['class' => 'rounded border px-4 py-3 text-sm '.($estilos[$tipo] ?? $estilos['info'])]) }}
>
    @if ($titulo)
        <p class="font-semibold">{{ $titulo }}</p>
    @endif

    <div>{{ $slot }}</div>
</div>
