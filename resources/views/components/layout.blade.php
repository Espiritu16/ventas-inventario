@props(['titulo' => null])

{{--
    Armazón único de toda pantalla del sistema. El HTML del documento, el
    encabezado y el menú viven acá y en ningún otro lado: una vista que los
    copiara quedaría desincronizada en cuanto uno de los tres cambiara.
--}}
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $titulo ? $titulo.' · '.config('app.name') : config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        @auth
            <div class="flex min-h-screen">
                <x-menu />

                <div class="flex min-w-0 flex-1 flex-col">
                    <x-encabezado :titulo="$titulo" />

                    <main class="flex-1 p-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            {{-- Sin sesión no hay menú ni encabezado que mostrar: la única
                 pantalla en este estado es la de acceso. --}}
            <main class="flex min-h-screen items-center justify-center p-6">
                {{ $slot }}
            </main>
        @endauth
    </body>
</html>
