<div class="space-y-4">
    @if ($error)
        <x-aviso tipo="error">{{ $error }}</x-aviso>
    @endif

    @if ($exito)
        <x-aviso tipo="exito">{{ $exito }}</x-aviso>
    @endif

    <div class="flex items-center justify-between gap-4">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" wire:model.live="incluirInactivas" data-prueba="incluir-inactivas">
            Ver también las inactivas
        </label>

        <button
            type="button"
            wire:click="nuevo"
            data-prueba="nueva-categoria"
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
        >
            Nueva categoría
        </button>
    </div>

    @if ($formularioAbierto)
        <div class="rounded border border-slate-200 bg-white p-4">
            <h2 class="mb-4 text-sm font-semibold">
                {{ $editando === null ? 'Nueva categoría' : 'Editar categoría' }}
            </h2>

            <x-formulario :enviar="$editando === null ? 'crear' : 'actualizar'" etiqueta-enviar="Guardar">
                <x-campo
                    nombre="nombre"
                    etiqueta="Nombre"
                    wire:model="nombre"
                    :error="$campoConError === 'nombre' ? $errorDeCampo : null"
                />

                <x-campo
                    nombre="descripcion"
                    etiqueta="Descripción"
                    wire:model="descripcion"
                    ayuda="Opcional."
                    :error="$campoConError === 'descripcion' ? $errorDeCampo : null"
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

    <x-tabla
        :vacio="$categorias->isEmpty()"
        mensaje-vacio="Todavía no hay categorías. Creá la primera para poder cargar productos."
    >
        <x-slot:encabezado>
            <th class="px-4 py-2 font-medium">Nombre</th>
            <th class="px-4 py-2 font-medium">Descripción</th>
            <th class="px-4 py-2 font-medium">Estado</th>
            <th class="px-4 py-2 font-medium"><span class="sr-only">Acciones</span></th>
        </x-slot:encabezado>

        @foreach ($categorias as $categoria)
            <tr data-prueba="fila-categoria" wire:key="categoria-{{ $categoria->id }}">
                <td class="px-4 py-2">{{ $categoria->nombre }}</td>
                <td class="px-4 py-2 text-slate-600">{{ $categoria->descripcion }}</td>
                <td class="px-4 py-2">
                    <span @class(['text-slate-500' => ! $categoria->activo])>
                        {{ $categoria->activo ? 'Activa' : 'Inactiva' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-right">
                    <button
                        type="button"
                        wire:click="editar({{ $categoria->id }})"
                        data-prueba="editar-categoria"
                        class="text-sm underline hover:text-slate-900"
                    >
                        Editar
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarEstado({{ $categoria->id }}, {{ $categoria->activo ? 'false' : 'true' }})"
                        wire:loading.attr="disabled"
                        data-prueba="cambiar-estado"
                        class="ml-3 text-sm underline hover:text-slate-900 disabled:opacity-50"
                    >
                        {{ $categoria->activo ? 'Desactivar' : 'Activar' }}
                    </button>
                </td>
            </tr>
        @endforeach
    </x-tabla>
</div>
