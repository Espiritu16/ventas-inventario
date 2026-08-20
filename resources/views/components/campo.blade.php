@props(['nombre', 'etiqueta', 'tipo' => 'text', 'error' => null, 'ayuda' => null])

{{--
    Campo de formulario con su etiqueta y su error de validación al lado, que
    es donde experiencia.md pide que aparezcan los errores de validación —a
    diferencia de los de negocio, que van como aviso de la operación.

    El campo queda asociado a su error por `aria-describedby`, para que un
    lector de pantalla lo anuncie junto al campo y no suelto al final.
--}}
@php
    $idCampo = 'campo-'.$nombre;
    $idError = $idCampo.'-error';
@endphp

<div class="space-y-1">
    <label for="{{ $idCampo }}" class="block text-sm font-medium text-slate-700">
        {{ $etiqueta }}
    </label>

    <input
        id="{{ $idCampo }}"
        name="{{ $nombre }}"
        type="{{ $tipo }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $idError }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900']) }}
    >

    @if ($ayuda)
        <p class="text-xs text-slate-500">{{ $ayuda }}</p>
    @endif

    @if ($error)
        <p id="{{ $idError }}" data-prueba="error-de-campo" class="text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
