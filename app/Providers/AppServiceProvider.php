<?php

namespace App\Providers;

use App\Compartido\Autorizacion\HookDePermisos;
use App\Compartido\Interfaz\RegistroDeComponentesLivewire;
use App\Compartido\Persistencia\GrammarPostgresConZonaHoraria;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Livewire::componentHook(HookDePermisos::class);
    }

    public function boot(): void
    {
        // Se aplica al establecerse la conexión, no al arrancar: así ningún
        // comando que no toque la base termina abriendo una.
        Event::listen(function (ConnectionEstablished $evento) {
            if ($evento->connection instanceof PostgresConnection) {
                $evento->connection->setQueryGrammar(
                    new GrammarPostgresConZonaHoraria($evento->connection)
                );
            }
        });

        RegistroDeComponentesLivewire::registrar(app_path('Dominios'));

        Livewire::componentHook(HookDePermisos::class);
    }
}
