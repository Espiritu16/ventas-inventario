<?php

namespace App\Dominios\Usuarios\Livewire;

use App\Compartido\Autorizacion\Permiso;
use Livewire\Component;

/**
 * Componente de humo: existe solo para demostrar que Livewire quedó operativo
 * sobre el layout base y que un componente ubicado dentro de su dominio se
 * descubre y responde a una interacción sin recargar la página.
 *
 * No es una pantalla ni parte de ningún RF. S-01-F lo reemplaza por las
 * pantallas reales y puede borrarlo sin consultar a nadie.
 */
#[Permiso('GET /panel')]
class HumoDeInstalacion extends Component
{
    public int $interacciones = 0;

    public function interactuar(): void
    {
        $this->interacciones++;
    }

    /**
     * La plantilla va en línea, y no en `resources/views/`, porque esa carpeta
     * es del frente de interfaz: un componente de humo del backend no tiene
     * por qué dejarle un archivo suelto en su territorio.
     */
    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <p>Livewire operativo. Interacciones:
                    <span data-prueba="conteo">{{ $interacciones }}</span>
                </p>
                <button type="button" wire:click="interactuar">Interactuar</button>
            </div>
        BLADE;
    }
}
