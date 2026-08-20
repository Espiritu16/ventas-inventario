<?php

namespace App\Dominios\Catalogo\Livewire;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Interfaz\MuestraRechazosDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Servicios\CategoriaService;
use App\Dominios\Catalogo\Servicios\ProductoService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Productos del catálogo (RF-004): listado, alta y edición en la misma
 * pantalla, con los dos precios.
 *
 * **No muestra existencias, y es a propósito.** Un producto no tiene stock:
 * el stock vive en los lotes que crea la compra, y lo sirve
 * `ConsultaDeInventarioService::stock()` desde S-04-B. Mostrar una columna en
 * cero acá diría "sin existencias" sobre algo que nunca se compró, que es
 * indistinguible de un producto agotado. Por eso el campo se omite en vez de
 * devolverse en cero, y esta pantalla no lo inventa.
 */
#[Permiso('GET /productos')]
class ListaDeProductos extends Component
{
    use MuestraRechazosDeDominio;

    /** Precisión decimal con la que el dominio compara precios. */
    private const DECIMALES = 4;

    #[Url]
    public string $buscar = '';

    #[Url]
    public ?int $categoriaId = null;

    #[Url]
    public int $pagina = 1;

    public bool $formularioAbierto = false;

    public ?int $editando = null;

    public string $codigo = '';

    public string $nombre = '';

    public ?int $categoriaDelFormulario = null;

    public string $unidadMedida = '';

    public string $precioMenor = '';

    public string $precioMayor = '';

    public string $stockMinimo = '';

    /**
     * Aviso inmediato de precios: aparece al escribir, antes de guardar.
     *
     * Es comodidad, no la protección. El servicio vuelve a rechazar la misma
     * combinación aunque esta comprobación se evite —y hay una prueba que lo
     * verifica llamando al método directamente—, por la misma razón por la
     * que ocultar un ítem del menú no es control de acceso.
     */
    public ?string $avisoDePrecios = null;

    public function updatedBuscar(): void
    {
        $this->pagina = 1;
    }

    public function updatedCategoriaId(): void
    {
        $this->pagina = 1;
    }

    public function updatedPrecioMenor(): void
    {
        $this->revisarPrecios();
    }

    public function updatedPrecioMayor(): void
    {
        $this->revisarPrecios();
    }

    /**
     * Se comparan como decimales exactos y no como números de punto flotante:
     * los precios viajan como texto justamente porque un `float` los redondea,
     * y redondear acá haría que la pantalla y el servicio discreparan sobre
     * dos precios que difieren en el último decimal.
     */
    private function revisarPrecios(): void
    {
        $this->avisoDePrecios = null;

        if (! $this->esDecimal($this->precioMenor) || ! $this->esDecimal($this->precioMayor)) {
            return;
        }

        if (bccomp($this->precioMayor, $this->precioMenor, self::DECIMALES) > 0) {
            $this->avisoDePrecios = 'El precio al por mayor no puede superar al precio al por menor.';
        }
    }

    private function esDecimal(string $valor): bool
    {
        return preg_match('/^\d{1,8}(\.\d{1,4})?$/', $valor) === 1;
    }

    public function nuevo(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id, ProductoService $productos): void
    {
        $this->limpiarMensajes();

        try {
            $producto = $productos->encontrar($id);
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->editando = $id;
        $this->codigo = $producto->codigo;
        $this->nombre = $producto->nombre;
        $this->categoriaDelFormulario = $producto->categoria_id;
        $this->unidadMedida = $producto->unidad_medida;
        $this->precioMenor = $producto->precio_menor;
        $this->precioMayor = $producto->precio_mayor;
        $this->stockMinimo = $producto->stock_minimo;
        $this->formularioAbierto = true;

        $this->revisarPrecios();
    }

    public function cancelar(): void
    {
        $this->limpiarFormulario();
    }

    #[Permiso('POST /productos')]
    public function crear(ProductoService $productos): void
    {
        $this->limpiarMensajes();

        try {
            $productos->crear(DatosDeCatalogo::desde([
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'categoria_id' => $this->categoriaDelFormulario,
                'unidad_medida' => $this->unidadMedida,
                'precio_menor' => $this->precioMenor,
                'precio_mayor' => $this->precioMayor,
                'stock_minimo' => $this->stockMinimo === '' ? '0' : $this->stockMinimo,
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Producto creado.';
        $this->limpiarFormulario();
    }

    /** El código no es modificable, así que no se envía. */
    #[Permiso('PATCH /productos/{id}')]
    public function actualizar(ProductoService $productos): void
    {
        $this->limpiarMensajes();

        try {
            $productos->actualizar((int) $this->editando, DatosDeCatalogo::desde([
                'nombre' => $this->nombre,
                'categoria_id' => $this->categoriaDelFormulario,
                'unidad_medida' => $this->unidadMedida,
                'precio_menor' => $this->precioMenor,
                'precio_mayor' => $this->precioMayor,
                'stock_minimo' => $this->stockMinimo,
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Producto actualizado.';
        $this->limpiarFormulario();
    }

    #[Permiso('PATCH /productos/{id}')]
    public function cambiarEstado(int $id, bool $activo, ProductoService $productos): void
    {
        $this->limpiarMensajes();

        try {
            $productos->actualizar($id, DatosDeCatalogo::desde(['activo' => $activo]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $activo ? 'Producto activado.' : 'Producto desactivado.';
    }

    private function limpiarFormulario(): void
    {
        $this->editando = null;
        $this->formularioAbierto = false;
        $this->codigo = '';
        $this->nombre = '';
        $this->categoriaDelFormulario = null;
        $this->unidadMedida = '';
        $this->precioMenor = '';
        $this->precioMayor = '';
        $this->stockMinimo = '';
        $this->avisoDePrecios = null;
        $this->errorDeCampo = null;
        $this->campoConError = null;
    }

    public function render()
    {
        return view('livewire.catalogo.lista-de-productos', [
            'productos' => app(ProductoService::class)->listar(
                $this->buscar === '' ? null : $this->buscar,
                $this->categoriaId,
                false,
                $this->pagina,
            ),
            // Solo las activas: el servicio rechaza crear un producto en una
            // categoría inactiva, así que ofrecerla sería ofrecer un rechazo.
            'categorias' => app(CategoriaService::class)->listar(),
            'unidades' => config('sunat.unidades_de_medida', []),
        ]);
    }
}
