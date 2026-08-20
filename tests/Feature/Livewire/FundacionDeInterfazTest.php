<?php

namespace Tests\Feature\Livewire;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * UT-01. La fundación compartida: el armazón, el menú armado por rol y los
 * componentes reutilizables.
 *
 * Lo que se comprueba del menú es experiencia de usuario, no protección. La
 * protección se comprueba aparte, en `ProteccionDeRutasTest`, y a propósito
 * sin mirar el menú: si una prueba de autorización pasara porque el ítem no
 * está visible, estaría comprobando el menú y no el control de acceso, y el
 * día que alguien navegue por URL directa nadie se enteraría.
 */
final class FundacionDeInterfazTest extends TestCase
{
    use RefreshDatabase;

    private function renderizar(string $plantilla): string
    {
        return Blade::render($plantilla);
    }

    // --- Menú armado por rol ---

    public function test_el_administrador_ve_la_seccion_de_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->assertStringContainsString('Usuarios', $this->renderizar('<x-menu />'));
    }

    /**
     * Si alguien quita la comprobación de rol del menú, esta prueba falla: los
     * ítems aparecerían para todos. Es la que la práctica de mutación busca.
     *
     * **Comprueba la relación, no el conjunto.** Dos versiones anteriores
     * fijaron una foto y caducaron por eso: primero "el vendedor no ve nada",
     * cierta solo mientras hubo una sola pantalla; después una lista escrita a
     * mano de las secciones ajenas, que habría quedado corta en cuanto
     * apareciera una nueva. Reparar una prueba que caducó no basta si la
     * reparación depende de lo mismo.
     *
     * Acá el universo de secciones sale de renderizar el menú, y la regla sale
     * de la matriz. Ninguna lista propia: una sección nueva queda cubierta el
     * día que se agrega, sin que nadie se acuerde de nada.
     *
     * **Su límite, dicho a propósito:** el universo se toma del menú del
     * administrador, así que comprueba *de las secciones que el menú ofrece,
     * el vendedor ve exactamente las que la matriz le permite*. No comprueba
     * que toda pantalla permitida tenga su ítem — una sección que no esté en
     * el menú para nadie es invisible para esta prueba. Es el recorte correcto
     * para su tema, que es qué ve el vendedor, y no un descuido.
     */
    public function test_el_vendedor_ve_exactamente_las_secciones_que_la_matriz_le_permite(): void
    {
        $vendedor = Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]);

        $ofrecidas = $this->seccionesDelMenuDe(Usuario::factory()->administrador()->create());
        $visibles = $this->seccionesDelMenuDe($vendedor);

        $this->assertNotEmpty($ofrecidas, 'El menú no ofreció ninguna sección: el barrido está mirando mal.');

        foreach ($ofrecidas as $ruta) {
            $permitida = MatrizDePermisos::permiteA('GET '.$ruta, $vendedor);

            $this->assertSame(
                $permitida,
                in_array($ruta, $visibles, true),
                "El menú y la matriz discrepan sobre «{$ruta}» para el vendedor."
            );
        }
    }

    /**
     * Las rutas que el menú ofrece a esa persona, leídas de lo renderizado.
     *
     * @return array<int, string>
     */
    private function seccionesDelMenuDe(Usuario $usuario): array
    {
        $this->actingAs($usuario);

        preg_match_all(
            '/<a\s[^>]*href="([^"]+)"[^>]*data-prueba="menu-item"/',
            $this->renderizar('<x-menu />'),
            $coincidencias
        );

        return $coincidencias[1];
    }

    /**
     * El menú se deriva de la misma matriz que autoriza en el servidor. Si se
     * copiara la lista de roles a la vista, las dos podrían discrepar sin que
     * nada fallara — y el menú es justamente el lado que no protege.
     */
    public function test_la_visibilidad_del_menu_coincide_con_la_matriz(): void
    {
        foreach ([Usuario::ROL_ADMINISTRADOR, Usuario::ROL_VENDEDOR] as $rol) {
            $usuario = Usuario::factory()->create(['rol' => $rol]);

            $this->actingAs($usuario);

            $visibleEnElMenu = str_contains($this->renderizar('<x-menu />'), 'Usuarios');
            $permitidoPorLaMatriz = MatrizDePermisos::permiteA('GET /usuarios', $usuario);

            $this->assertSame(
                $permitidoPorLaMatriz,
                $visibleEnElMenu,
                "El menú y la matriz discrepan para el rol «{$rol}»."
            );
        }
    }

    public function test_sin_sesion_el_menu_no_ofrece_nada(): void
    {
        $this->assertStringNotContainsString('Usuarios', $this->renderizar('<x-menu />'));
    }

    // --- Armazón único ---

    public function test_el_armazon_sirve_los_assets_compilados(): void
    {
        $html = $this->renderizar('<x-layout>contenido</x-layout>');

        $this->assertStringContainsString('<html lang="es">', $html);
        $this->assertStringContainsString('/build/assets/', $html);
        $this->assertStringContainsString('contenido', $html);
    }

    public function test_con_sesion_el_armazon_muestra_encabezado_y_menu(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create(['nombre' => 'Ana Quispe']));

        $html = $this->renderizar('<x-layout>contenido</x-layout>');

        $this->assertStringContainsString('Ana Quispe', $html);
        $this->assertStringContainsString('Cerrar sesión', $html);
        $this->assertStringContainsString('Menú principal', $html);
    }

    public function test_sin_sesion_el_armazon_no_muestra_menu_ni_encabezado(): void
    {
        $html = $this->renderizar('<x-layout>contenido</x-layout>');

        $this->assertStringNotContainsString('Cerrar sesión', $html);
        $this->assertStringNotContainsString('Menú principal', $html);
    }

    /**
     * El HTML del documento vive una sola vez. Una vista que lo copiara
     * quedaría desincronizada en cuanto el armazón cambiara, y nadie se
     * enteraría hasta verlo en pantalla.
     */
    public function test_ninguna_vista_duplica_el_html_del_documento(): void
    {
        $duplicados = [];

        foreach ($this->vistasDelProyecto() as $archivo) {
            if ($archivo === resource_path('views/components/layout.blade.php')) {
                continue;
            }

            if (str_contains((string) file_get_contents($archivo), '<html')) {
                $duplicados[] = str_replace(resource_path('views/'), '', $archivo);
            }
        }

        $this->assertSame([], $duplicados, 'Solo <x-layout> puede contener el HTML del documento.');
    }

    /** El cierre de sesión escribe, así que no puede colgar de un enlace. */
    public function test_el_cierre_de_sesion_va_por_post(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $html = $this->renderizar('<x-layout>contenido</x-layout>');

        $this->assertMatchesRegularExpression('/<form[^>]*method="POST"[^>]*action="\/logout"/', $html);
        $this->assertStringNotContainsString('<a href="/logout"', $html);
    }

    // --- Componentes reutilizables ---

    public function test_la_tabla_expone_sus_cuatro_estados(): void
    {
        $this->assertStringContainsString('tabla-cargando', $this->renderizar('<x-tabla :cargando="true" />'));
        $this->assertStringContainsString('tabla-error', $this->renderizar('<x-tabla error="Algo falló" />'));
        $this->assertStringContainsString('tabla-vacia', $this->renderizar('<x-tabla :vacio="true" />'));
        $this->assertStringContainsString('tabla-filas', $this->renderizar('<x-tabla><tr><td>fila</td></tr></x-tabla>'));
    }

    public function test_el_aviso_distingue_error_de_exito(): void
    {
        $error = $this->renderizar('<x-aviso tipo="error">No se pudo</x-aviso>');
        $exito = $this->renderizar('<x-aviso tipo="exito">Guardado</x-aviso>');

        $this->assertStringContainsString('role="alert"', $error);
        $this->assertStringContainsString('role="status"', $exito);
    }

    /** Bloquear el botón mientras la operación corre (integracion.md). */
    public function test_el_formulario_se_bloquea_mientras_envia(): void
    {
        $html = $this->renderizar('<x-formulario enviar="guardar">campos</x-formulario>');

        $this->assertStringContainsString('wire:loading.attr="disabled"', $html);
        $this->assertStringContainsString('wire:target="guardar"', $html);
    }

    public function test_la_confirmacion_pide_una_accion_explicita(): void
    {
        $html = $this->renderizar('<x-confirmacion mensaje="¿Seguro?" confirmar="borrar" cancelar="cerrar" />');

        $this->assertStringContainsString('role="alertdialog"', $html);
        $this->assertStringContainsString('wire:click="borrar"', $html);
        $this->assertStringContainsString('wire:click="cerrar"', $html);
    }

    /** El error de validación va junto al campo y anunciado por el lector. */
    public function test_el_campo_asocia_su_error_para_el_lector_de_pantalla(): void
    {
        $html = $this->renderizar('<x-campo nombre="email" etiqueta="Correo" error="Falta el correo" />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="campo-email-error"', $html);
        $this->assertStringContainsString('id="campo-email-error"', $html);
    }

    /** @return array<int, string> */
    private function vistasDelProyecto(): array
    {
        $archivos = [];
        $directorio = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($directorio as $archivo) {
            if ($archivo->isFile() && str_ends_with($archivo->getFilename(), '.blade.php')) {
                $archivos[] = $archivo->getPathname();
            }
        }

        return $archivos;
    }
}
