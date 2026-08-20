<div class="space-y-4">
    <x-mensajes :error="$error" :exito="$exito" />

    <div class="flex items-center justify-between gap-4">
        <input
            type="search"
            wire:model.live.debounce.300ms="buscar"
            placeholder="Buscar por nombre o correo"
            aria-label="Buscar usuarios"
            data-prueba="buscar"
            class="w-72 rounded border border-slate-300 px-3 py-2 text-sm"
        >

        <button
            type="button"
            wire:click="nuevo"
            data-prueba="nuevo-usuario"
            class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
        >
            Nuevo usuario
        </button>
    </div>

    @if ($formularioAbierto)
        <div class="rounded border border-slate-200 bg-white p-4">
            <h2 class="mb-4 text-sm font-semibold">
                {{ $editando === null ? 'Nuevo usuario' : 'Editar usuario' }}
            </h2>

            <x-formulario :enviar="$editando === null ? 'crear' : 'actualizar'" etiqueta-enviar="Guardar">
                <x-campo
                    nombre="nombre"
                    etiqueta="Nombre"
                    wire:model="nombre"
                    :error="$this->errorDe('nombre')"
                />

                <x-campo
                    nombre="email"
                    etiqueta="Correo"
                    tipo="email"
                    wire:model="email"
                    :error="$this->errorDe('email')"
                />

                @if ($editando === null)
                    <x-campo
                        nombre="password"
                        etiqueta="Contraseña"
                        tipo="password"
                        wire:model="password"
                        ayuda="Mínimo 8 caracteres."
                        :error="$this->errorDe('password')"
                    />
                @endif

                <div class="space-y-1">
                    <label for="campo-rol" class="block text-sm font-medium text-slate-700">Rol</label>
                    <select
                        id="campo-rol"
                        wire:model="rol"
                        data-prueba="campo-rol"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    >
                        @foreach (\App\Dominios\Usuarios\Modelos\Usuario::ROLES as $opcion)
                            <option value="{{ $opcion }}">{{ ucfirst($opcion) }}</option>
                        @endforeach
                    </select>

                    @if ($this->errorDe('rol'))
                        <p data-prueba="error-de-campo" class="text-sm text-red-700">{{ $errorDeCampo }}</p>
                    @endif
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
        :vacio="$usuarios->total() === 0"
        mensaje-vacio="No hay usuarios que coincidan con la búsqueda."
    >
        <x-slot:encabezado>
            <th class="px-4 py-2 font-medium">Nombre</th>
            <th class="px-4 py-2 font-medium">Correo</th>
            <th class="px-4 py-2 font-medium">Rol</th>
            <th class="px-4 py-2 font-medium">Estado</th>
            <th class="px-4 py-2 font-medium"><span class="sr-only">Acciones</span></th>
        </x-slot:encabezado>

        @foreach ($usuarios as $usuario)
            <tr data-prueba="fila-usuario" wire:key="usuario-{{ $usuario->id }}">
                <td class="px-4 py-2">{{ $usuario->nombre }}</td>
                <td class="px-4 py-2">{{ $usuario->email }}</td>
                <td class="px-4 py-2">{{ ucfirst($usuario->rol) }}</td>
                <td class="px-4 py-2">
                    <span @class(['text-slate-500' => ! $usuario->activo])>
                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td class="px-4 py-2 text-right">
                    <button
                        type="button"
                        wire:click="editar({{ $usuario->id }})"
                        data-prueba="editar-usuario"
                        class="text-sm underline hover:text-slate-900"
                    >
                        Editar
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarEstado({{ $usuario->id }}, {{ $usuario->activo ? 'false' : 'true' }})"
                        wire:loading.attr="disabled"
                        wire:target="cambiarEstado({{ $usuario->id }}, {{ $usuario->activo ? 'false' : 'true' }})"
                        data-prueba="cambiar-estado"
                        class="ml-3 text-sm underline hover:text-slate-900 disabled:opacity-50"
                    >
                        {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                    </button>
                </td>
            </tr>
        @endforeach
    </x-tabla>

    @if ($usuarios->hasPages())
        <div data-prueba="paginacion">
            {{ $usuarios->links() }}
        </div>
    @endif
</div>
