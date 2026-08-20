<div class="space-y-4">
    <x-mensajes :error="$error" :exito="$exito" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por RUC o razón social"
                aria-label="Buscar proveedores"
                data-prueba="buscar"
                class="w-72 rounded border border-slate-300 px-3 py-2 text-sm"
            >

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="incluirInactivos" data-prueba="incluir-inactivos">
                Ver también los inactivos
            </label>
        </div>

        <button
            type="button"
            wire:click="nuevo"
            data-prueba="nuevo-proveedor"
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
        >
            Nuevo proveedor
        </button>
    </div>

    @if ($formularioAbierto)
        <div class="rounded border border-slate-200 bg-white p-4">
            <h2 class="mb-4 text-sm font-semibold">
                {{ $editando === null ? 'Nuevo proveedor' : 'Editar proveedor' }}
            </h2>

            <x-formulario :enviar="$editando === null ? 'crear' : 'actualizar'" etiqueta-enviar="Guardar">
                @if ($editando === null)
                    <x-campo
                        nombre="numero_documento"
                        etiqueta="RUC"
                        wire:model.live.debounce.300ms="numeroDocumento"
                        inputmode="numeric"
                        ayuda="11 dígitos."
                        :error="$campoConError === 'numero_documento' ? $errorDeCampo : null"
                    />

                    @if ($avisoDeDocumento)
                        <p data-prueba="aviso-de-documento" class="text-sm text-amber-700">{{ $avisoDeDocumento }}</p>
                    @endif
                @else
                    <p class="text-sm text-slate-600">
                        RUC: <span class="font-medium">{{ $numeroDocumento }}</span>
                        <span class="text-slate-500">— no se puede cambiar.</span>
                    </p>
                @endif

                <x-campo
                    nombre="razon_social"
                    etiqueta="Razón social"
                    wire:model="razonSocial"
                    :error="$campoConError === 'razon_social' ? $errorDeCampo : null"
                />

                <x-campo
                    nombre="direccion"
                    etiqueta="Dirección"
                    wire:model="direccion"
                    ayuda="Opcional."
                    :error="$campoConError === 'direccion' ? $errorDeCampo : null"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-campo
                        nombre="telefono"
                        etiqueta="Teléfono"
                        wire:model="telefono"
                        ayuda="Opcional."
                        :error="$campoConError === 'telefono' ? $errorDeCampo : null"
                    />

                    <x-campo
                        nombre="email"
                        etiqueta="Correo"
                        tipo="email"
                        wire:model="email"
                        ayuda="Opcional."
                        :error="$campoConError === 'email' ? $errorDeCampo : null"
                    />
                </div>

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
        :vacio="$proveedores->total() === 0"
        mensaje-vacio="No hay proveedores que coincidan con la búsqueda."
    >
        <x-slot:encabezado>
            <th class="px-4 py-2 font-medium">RUC</th>
            <th class="px-4 py-2 font-medium">Razón social</th>
            <th class="px-4 py-2 font-medium">Contacto</th>
            <th class="px-4 py-2 font-medium">Estado</th>
            <th class="px-4 py-2 font-medium"><span class="sr-only">Acciones</span></th>
        </x-slot:encabezado>

        @foreach ($proveedores as $proveedor)
            <tr data-prueba="fila-proveedor" wire:key="proveedor-{{ $proveedor->id }}">
                <td class="px-4 py-2 font-mono text-xs">{{ $proveedor->numero_documento }}</td>
                <td class="px-4 py-2">{{ $proveedor->razon_social }}</td>
                <td class="px-4 py-2 text-slate-600">{{ $proveedor->telefono }} {{ $proveedor->email }}</td>
                <td class="px-4 py-2">
                    <span @class(['text-slate-500' => ! $proveedor->activo])>
                        {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-right">
                    <button
                        type="button"
                        wire:click="editar({{ $proveedor->id }})"
                        data-prueba="editar-proveedor"
                        class="text-sm underline hover:text-slate-900"
                    >
                        Editar
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarEstado({{ $proveedor->id }}, {{ $proveedor->activo ? 'false' : 'true' }})"
                        wire:loading.attr="disabled"
                        data-prueba="cambiar-estado"
                        class="ml-3 text-sm underline hover:text-slate-900 disabled:opacity-50"
                    >
                        {{ $proveedor->activo ? 'Desactivar' : 'Activar' }}
                    </button>
                </td>
            </tr>
        @endforeach
    </x-tabla>

    @if ($proveedores->hasPages())
        <div data-prueba="paginacion">{{ $proveedores->links() }}</div>
    @endif
</div>
