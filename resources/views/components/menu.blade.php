@php
    use App\Compartido\Autorizacion\MatrizDePermisos;

    /**
     * Menú lateral, armado según el rol de quien tiene la sesión abierta.
     *
     * Cada ítem declara la ruta que abre y el identificador con el que esa
     * ruta figura en la matriz de permisos. La visibilidad no se decide acá:
     * se le pregunta a la misma matriz que el servidor usa para autorizar. Si
     * se copiara la lista de roles a este archivo, el menú y el control real
     * podrían discrepar sin que nada fallara, y el menú es justamente el lado
     * que no protege nada.
     *
     * Ocultar un ítem es comodidad, no control de acceso: entrar por URL
     * directa a algo no permitido lo rechaza el servidor igual (RNF-013).
     * Que un ítem no se vea nunca es la razón por la que algo está protegido.
     *
     * Un ítem cuya ruta todavía no está declarada en la matriz no aparece
     * para nadie. Es deny-by-default aplicado también al menú: cada sprint
     * que agrega una pantalla la declara, y recién ahí se vuelve navegable.
     */
    $items = [
        ['etiqueta' => 'Categorías', 'ruta' => '/categorias', 'identificador' => 'GET /categorias'],
        ['etiqueta' => 'Usuarios', 'ruta' => '/usuarios', 'identificador' => 'GET /usuarios'],
    ];

    $usuario = auth()->user();

    $visibles = $usuario === null ? [] : array_values(array_filter(
        $items,
        fn (array $item) => MatrizDePermisos::permiteA($item['identificador'], $usuario)
    ));
@endphp

<nav class="w-56 shrink-0 border-r border-slate-200 bg-white p-4" aria-label="Menú principal">
    <p class="px-2 pb-4 text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>

    @if ($visibles === [])
        <p class="px-2 text-sm text-slate-500" data-prueba="menu-vacio">
            No hay secciones disponibles para tu rol.
        </p>
    @else
        <ul class="space-y-1">
            @foreach ($visibles as $item)
                <li>
                    <a
                        href="{{ $item['ruta'] }}"
                        data-prueba="menu-item"
                        @class([
                            'block rounded px-2 py-1.5 text-sm hover:bg-slate-100',
                            'bg-slate-100 font-medium' => request()->is(ltrim($item['ruta'], '/').'*'),
                        ])
                        @if (request()->is(ltrim($item['ruta'], '/').'*')) aria-current="page" @endif
                    >
                        {{ $item['etiqueta'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</nav>
