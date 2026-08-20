@props([
    'cargando' => false,
    'error' => null,
    'vacio' => false,
    'mensajeVacio' => 'Todavía no hay nada que mostrar.',
])

{{--
    Tabla de listado con los cuatro estados visibles que declara
    docs/frontend/experiencia.md: carga, error, vacío y éxito. Están acá y no
    en cada pantalla para que ninguna se olvide de alguno —el estado vacío es
    el que más se omite, y sin él una lista sin resultados parece un fallo.

    El encabezado de columnas va en el slot `encabezado`; las filas, en el
    slot por defecto.
--}}
<div {{ $attributes->merge(['class' => 'rounded border border-slate-200 bg-white']) }} data-prueba="tabla">
    @if ($cargando)
        <p class="px-4 py-6 text-sm text-slate-600" role="status" data-prueba="tabla-cargando">
            Cargando…
        </p>
    @elseif ($error)
        <div class="p-4">
            <x-aviso tipo="error" data-prueba="tabla-error">{{ $error }}</x-aviso>
        </div>
    @elseif ($vacio)
        <p class="px-4 py-6 text-sm text-slate-600" data-prueba="tabla-vacia">
            {{ $mensajeVacio }}
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                @isset($encabezado)
                    <thead class="border-b border-slate-200 text-slate-600">
                        <tr>{{ $encabezado }}</tr>
                    </thead>
                @endisset

                <tbody class="divide-y divide-slate-100" data-prueba="tabla-filas">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    @endif
</div>
