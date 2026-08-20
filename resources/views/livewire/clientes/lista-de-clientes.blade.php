<div class="space-y-4">
    <x-mensajes :error="$error" :exito="$exito" />

    @unless ($comoDialogo)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <input
                type="search"
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por documento o nombre"
                aria-label="Buscar clientes"
                data-prueba="buscar"
                class="w-72 rounded border border-slate-300 px-3 py-2 text-sm"
            >

            <button
                type="button"
                wire:click="nuevo"
                data-prueba="nuevo-cliente"
                class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
            >
                Nuevo cliente
            </button>
        </div>
    @endunless

    @if ($formularioAbierto)
        <div class="rounded border border-slate-200 bg-white p-4">
            <h2 class="mb-4 text-sm font-semibold">
                {{ $editando === null ? 'Nuevo cliente' : 'Editar cliente' }}
            </h2>

            <x-formulario :enviar="$editando === null ? 'crear' : 'actualizar'" etiqueta-enviar="Guardar">
                @if ($editando === null)
                    <div class="space-y-1">
                        <label for="campo-tipo" class="block text-sm font-medium text-slate-700">Tipo de documento</label>
                        <select
                            id="campo-tipo"
                            wire:model.live="tipoDocumento"
                            data-prueba="campo-tipo"
                            class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                        >
                            @foreach ($tipos as $tipo)
                                <option value="{{ $tipo->value }}">{{ ucfirst($tipo->descripcion()) }}</option>
                            @endforeach
                        </select>

                        @if ($this->errorDe('tipo_documento'))
                            <p data-prueba="error-de-campo" class="text-sm text-red-700">{{ $errorDeCampo }}</p>
                        @endif
                    </div>

                    <x-campo
                        nombre="numero_documento"
                        etiqueta="Número de documento"
                        wire:model.live.debounce.300ms="numeroDocumento"
                        :error="$this->errorDe('numero_documento')"
                    />

                    @if ($avisoDeDocumento)
                        <p data-prueba="aviso-de-documento" class="text-sm text-amber-700">{{ $avisoDeDocumento }}</p>
                    @endif
                @else
                    <p class="text-sm text-slate-600">
                        Documento: <span class="font-medium">{{ $numeroDocumento }}</span>
                        <span class="text-slate-500">— no se puede cambiar.</span>
                    </p>
                @endif

                <x-campo
                    nombre="nombre"
                    etiqueta="Nombre o razón social"
                    wire:model="nombre"
                    :error="$this->errorDe('nombre')"
                />

                <x-campo
                    nombre="direccion"
                    etiqueta="Dirección"
                    wire:model="direccion"
                    ayuda="Opcional."
                    :error="$this->errorDe('direccion')"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-campo
                        nombre="telefono"
                        etiqueta="Teléfono"
                        wire:model="telefono"
                        ayuda="Opcional."
                        :error="$this->errorDe('telefono')"
                    />

                    <x-campo
                        nombre="email"
                        etiqueta="Correo"
                        tipo="email"
                        wire:model="email"
                        ayuda="Opcional."
                        :error="$this->errorDe('email')"
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

    @unless ($comoDialogo)
        <x-tabla
            :vacio="$clientes->total() === 0"
            mensaje-vacio="No hay clientes que coincidan con la búsqueda."
        >
            <x-slot:encabezado>
                <th class="px-4 py-2 font-medium">Documento</th>
                <th class="px-4 py-2 font-medium">Nombre</th>
                <th class="px-4 py-2 font-medium">Contacto</th>
                <th class="px-4 py-2 font-medium"><span class="sr-only">Acciones</span></th>
            </x-slot:encabezado>

            @foreach ($clientes as $cliente)
                <tr data-prueba="fila-cliente" wire:key="cliente-{{ $cliente->id }}">
                    <td class="px-4 py-2 font-mono text-xs">{{ $cliente->numero_documento }}</td>
                    <td class="px-4 py-2">{{ $cliente->nombre }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ $cliente->telefono }} {{ $cliente->email }}</td>
                    <td class="px-4 py-2 text-right">
                        {{-- Comodidad: quien no puede editar no ve el botón. La
                             protección la aplica el mecanismo, no esta condición. --}}
                        @if ($this->puedeEditar())
                            <button
                                type="button"
                                wire:click="editar({{ $cliente->id }})"
                                data-prueba="editar-cliente"
                                class="text-sm underline hover:text-slate-900"
                            >
                                Editar
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-tabla>

        @if ($clientes->hasPages())
            <div data-prueba="paginacion">{{ $clientes->links() }}</div>
        @endif
    @endunless
</div>
