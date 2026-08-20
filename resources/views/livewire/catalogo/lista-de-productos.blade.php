<div class="space-y-4">
    @if ($error)
        <x-aviso tipo="error">{{ $error }}</x-aviso>
    @endif

    @if ($exito)
        <x-aviso tipo="exito">{{ $exito }}</x-aviso>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por código o nombre"
                aria-label="Buscar productos"
                data-prueba="buscar"
                class="w-64 rounded border border-slate-300 px-3 py-2 text-sm"
            >

            <select
                wire:model.live="categoriaId"
                aria-label="Filtrar por categoría"
                data-prueba="filtro-categoria"
                class="rounded border border-slate-300 px-3 py-2 text-sm"
            >
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>

        <button
            type="button"
            wire:click="nuevo"
            data-prueba="nuevo-producto"
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
        >
            Nuevo producto
        </button>
    </div>

    @if ($formularioAbierto)
        <div class="rounded border border-slate-200 bg-white p-4">
            <h2 class="mb-4 text-sm font-semibold">
                {{ $editando === null ? 'Nuevo producto' : 'Editar producto' }}
            </h2>

            <x-formulario :enviar="$editando === null ? 'crear' : 'actualizar'" etiqueta-enviar="Guardar">
                @if ($editando === null)
                    <x-campo
                        nombre="codigo"
                        etiqueta="Código"
                        wire:model="codigo"
                        ayuda="Letras mayúsculas, números, guion y guion bajo."
                        :error="$campoConError === 'codigo' ? $errorDeCampo : null"
                    />
                @else
                    <p class="text-sm text-slate-600">
                        Código: <span class="font-medium">{{ $codigo }}</span>
                        <span class="text-slate-500">— no se puede cambiar.</span>
                    </p>
                @endif

                <x-campo
                    nombre="nombre"
                    etiqueta="Nombre"
                    wire:model="nombre"
                    :error="$campoConError === 'nombre' ? $errorDeCampo : null"
                />

                <div class="space-y-1">
                    <label for="campo-categoria" class="block text-sm font-medium text-slate-700">Categoría</label>
                    <select
                        id="campo-categoria"
                        wire:model="categoriaDelFormulario"
                        data-prueba="campo-categoria"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">Elegí una categoría</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>

                    @if ($campoConError === 'categoria_id')
                        <p data-prueba="error-de-campo" class="text-sm text-red-700">{{ $errorDeCampo }}</p>
                    @endif
                </div>

                <div class="space-y-1">
                    <label for="campo-unidad" class="block text-sm font-medium text-slate-700">Unidad de medida</label>
                    <select
                        id="campo-unidad"
                        wire:model="unidadMedida"
                        data-prueba="campo-unidad"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">Elegí una unidad</option>
                        @foreach ($unidades as $unidad)
                            <option value="{{ $unidad }}">{{ $unidad }}</option>
                        @endforeach
                    </select>

                    @if ($campoConError === 'unidad_medida')
                        <p data-prueba="error-de-campo" class="text-sm text-red-700">{{ $errorDeCampo }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-campo
                        nombre="precio_menor"
                        etiqueta="Precio al por menor"
                        wire:model.live.debounce.300ms="precioMenor"
                        inputmode="decimal"
                        :error="$campoConError === 'precio_menor' ? $errorDeCampo : null"
                    />

                    <x-campo
                        nombre="precio_mayor"
                        etiqueta="Precio al por mayor"
                        wire:model.live.debounce.300ms="precioMayor"
                        inputmode="decimal"
                        :error="$campoConError === 'precio_mayor' ? $errorDeCampo : null"
                    />
                </div>

                {{-- Aviso al escribir. El servidor vuelve a rechazarlo igual. --}}
                @if ($avisoDePrecios)
                    <p data-prueba="aviso-de-precios" class="text-sm text-amber-700">{{ $avisoDePrecios }}</p>
                @endif

                <x-campo
                    nombre="stock_minimo"
                    etiqueta="Stock mínimo"
                    wire:model="stockMinimo"
                    inputmode="decimal"
                    ayuda="Opcional. Se usa para avisar cuándo reponer."
                    :error="$campoConError === 'stock_minimo' ? $errorDeCampo : null"
                />

                <x-slot:acciones>
                    <button
                        type="button"
                        wire:click="cancelar"
                        data-prueba="cancelar"
                        class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50"
                    >
                        Cancelar
                    </button>
                </x-slot:acciones>
            </x-formulario>
        </div>
    @endif

    {{--
        Sin columna de existencias: un producto no las tiene. El stock vive en
        los lotes y llega en S-04-B. Una columna en cero diría "sin stock"
        sobre algo que nunca se compró.
    --}}
    <x-tabla
        :vacio="$productos->total() === 0"
        mensaje-vacio="No hay productos que coincidan con la búsqueda."
    >
        <x-slot:encabezado>
            <th class="px-4 py-2 font-medium">Código</th>
            <th class="px-4 py-2 font-medium">Nombre</th>
            <th class="px-4 py-2 font-medium">Categoría</th>
            <th class="px-4 py-2 font-medium">Unidad</th>
            <th class="px-4 py-2 text-right font-medium">Menor</th>
            <th class="px-4 py-2 text-right font-medium">Mayor</th>
            <th class="px-4 py-2 font-medium">Estado</th>
            <th class="px-4 py-2 font-medium"><span class="sr-only">Acciones</span></th>
        </x-slot:encabezado>

        @foreach ($productos as $producto)
            <tr data-prueba="fila-producto" wire:key="producto-{{ $producto->id }}">
                <td class="px-4 py-2 font-mono text-xs">{{ $producto->codigo }}</td>
                <td class="px-4 py-2">{{ $producto->nombre }}</td>
                <td class="px-4 py-2 text-slate-600">{{ $producto->categoria?->nombre }}</td>
                <td class="px-4 py-2 text-slate-600">{{ $producto->unidad_medida }}</td>
                <td class="px-4 py-2 text-right tabular-nums">{{ $producto->precio_menor }}</td>
                <td class="px-4 py-2 text-right tabular-nums">{{ $producto->precio_mayor }}</td>
                <td class="px-4 py-2">
                    <span @class(['text-slate-500' => ! $producto->activo])>
                        {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-right">
                    <button
                        type="button"
                        wire:click="editar({{ $producto->id }})"
                        data-prueba="editar-producto"
                        class="text-sm underline hover:text-slate-900"
                    >
                        Editar
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarEstado({{ $producto->id }}, {{ $producto->activo ? 'false' : 'true' }})"
                        wire:loading.attr="disabled"
                        data-prueba="cambiar-estado"
                        class="ml-3 text-sm underline hover:text-slate-900 disabled:opacity-50"
                    >
                        {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                    </button>
                </td>
            </tr>
        @endforeach
    </x-tabla>

    @if ($productos->hasPages())
        <div data-prueba="paginacion">{{ $productos->links() }}</div>
    @endif
</div>
