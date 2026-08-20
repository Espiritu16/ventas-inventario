@props([
    'mensaje',
    'confirmar',
    'cancelar' => null,
    'etiquetaConfirmar' => 'Confirmar',
    'etiquetaCancelar' => 'Cancelar',
    'titulo' => null,
])

{{--
    Confirmación de una acción que no se deshace sola. Se monta solo cuando
    hace falta; quien la use decide cuándo mostrarla.

    El botón de confirmar se bloquea mientras la operación corre, por la misma
    razón que en el formulario: el doble clic es el caso frecuente.
--}}
<div
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="confirmacion-titulo"
    data-prueba="confirmacion"
    {{ $attributes->merge(['class' => 'rounded border border-slate-300 bg-white p-4 shadow-sm']) }}
>
    <p id="confirmacion-titulo" class="font-semibold text-slate-900">
        {{ $titulo ?? '¿Confirmas la acción?' }}
    </p>

    <p class="mt-1 text-sm text-slate-700">{{ $mensaje }}</p>

    <div class="mt-4 flex items-center gap-3">
        <button
            type="button"
            wire:click="{{ $confirmar }}"
            wire:loading.attr="disabled"
            wire:target="{{ $confirmar }}"
            data-prueba="confirmacion-confirmar"
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {{ $etiquetaConfirmar }}
        </button>

        @if ($cancelar)
            <button
                type="button"
                wire:click="{{ $cancelar }}"
                data-prueba="confirmacion-cancelar"
                class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50"
            >
                {{ $etiquetaCancelar }}
            </button>
        @endif
    </div>
</div>
