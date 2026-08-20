@props(['titulo' => null])

{{-- Encabezado de la pantalla: título y salida de sesión. --}}
<header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
    <h1 class="text-lg font-semibold">{{ $titulo ?? config('app.name') }}</h1>

    <div class="flex items-center gap-4">
        <span class="text-sm text-slate-600" data-prueba="usuario-actual">
            {{ auth()->user()->nombre }}
        </span>

        {{-- El cierre de sesión es una escritura: va por POST, nunca por un
             enlace, para que no lo dispare una precarga del navegador. --}}
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="text-sm text-slate-600 underline hover:text-slate-900">
                Cerrar sesión
            </button>
        </form>
    </div>
</header>
