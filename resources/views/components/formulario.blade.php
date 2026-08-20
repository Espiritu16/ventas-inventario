@props([
    'enviar' => null,
    'etiquetaEnviar' => 'Guardar',
    'error' => null,
])

{{--
    Formulario con protección contra doble envío: mientras la operación está
    en curso el botón queda deshabilitado (docs/frontend/integracion.md).
    Bloquear el botón no protege de una recarga —eso se resuelve con la clave
    de idempotencia donde el contrato la exige—, pero sí del doble clic, que
    es el caso frecuente.

    `enviar` es el método del componente Livewire que confirma. Se usa además
    como `wire:target`, para que el bloqueo se active solo con esta operación
    y no con cualquier otra cosa que el componente esté haciendo.
--}}
<form
    @if ($enviar) wire:submit="{{ $enviar }}" @endif
    {{ $attributes->merge(['class' => 'space-y-4']) }}
    data-prueba="formulario"
>
    @if ($error)
        <x-aviso tipo="error">{{ $error }}</x-aviso>
    @endif

    {{ $slot }}

    <div class="flex items-center gap-3">
        <button
            type="submit"
            data-prueba="formulario-enviar"
            @if ($enviar)
                wire:loading.attr="disabled"
                wire:target="{{ $enviar }}"
            @endif
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {{ $etiquetaEnviar }}
        </button>

        @if ($enviar)
            <span wire:loading wire:target="{{ $enviar }}" class="text-sm text-slate-600" role="status">
                Enviando…
            </span>
        @endif

        {{ $acciones ?? '' }}
    </div>
</form>
