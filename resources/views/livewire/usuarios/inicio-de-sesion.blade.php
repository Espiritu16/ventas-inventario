{{--
    El foco vuelve al campo de correo cuando el intento falla. Se hace con un
    evento del componente y no con `autofocus`, porque el navegador solo honra
    `autofocus` al cargar la página y acá el fallo llega como actualización
    del DOM, sin recarga.
--}}
<div
    class="w-full max-w-sm"
    x-data
    x-on:foco-al-correo.window="$refs.correo && $refs.correo.focus()"
>
    <h1 class="mb-6 text-center text-xl font-semibold">{{ config('app.name') }}</h1>

    <x-formulario enviar="iniciar" etiqueta-enviar="Entrar" :error="$error">
        <x-campo
            nombre="email"
            etiqueta="Correo"
            tipo="email"
            wire:model="email"
            x-ref="correo"
            autocomplete="username"
            required
        />

        <x-campo
            nombre="password"
            etiqueta="Contraseña"
            tipo="password"
            wire:model="password"
            autocomplete="current-password"
            required
        />
    </x-formulario>
</div>
