<?php

namespace App\Dominios\Inventario\Servicios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Lecturas de inventario y kardex (RF-007, RF-008).
 *
 * Separado de `InventarioService` a propósito: aquel es la única puerta de
 * **escritura** y una prueba de arquitectura lo verifica. Mezclar consultas
 * ahí volvería esa regla más difícil de sostener y de leer.
 *
 * La proyección por rol se resuelve acá y no en la pantalla: ocultar una
 * columna en la vista no es control de acceso, y el mismo dato se sirve a
 * componentes distintos.
 */
class ConsultaDeInventarioService
{
    private const POR_PAGINA = 20;

    private const DIAS_POR_DEFECTO = 30;

    private const RANGO_MAXIMO_DIAS = 366;

    public function __construct(private readonly InventarioService $inventario) {}

    /**
     * Stock por producto, con sus lotes ordenados por vencimiento más próximo.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function stock(
        Usuario $actor,
        ?string $buscar = null,
        ?int $categoriaId = null,
        bool $soloConStock = false,
        int $pagina = 1,
    ): LengthAwarePaginator {
        $hoy = now()->format('Y-m-d');

        $productos = Producto::query()
            ->where('activo', true)
            ->when($categoriaId !== null, fn ($c) => $c->where('categoria_id', $categoriaId))
            ->when($buscar !== null && trim($buscar) !== '', function ($consulta) use ($buscar) {
                $texto = '%'.trim($buscar).'%';

                $consulta->where(fn ($g) => $g->where('codigo', 'ilike', $texto)->orWhere('nombre', 'ilike', $texto));
            })
            ->when($soloConStock, fn ($c) => $c->whereHas('lotes', fn ($l) => $l->where('cantidad_actual', '>', 0)))
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));

        return $productos->through(fn (Producto $producto) => [
            'id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'stock_disponible' => $this->inventario->stockDisponible((int) $producto->id, $hoy),
            'lotes' => $this->inventario->lotesDe((int) $producto->id)
                ->map(fn (Lote $lote) => $this->presentarLote($lote, $actor, $hoy))
                ->all(),
        ]);
    }

    /**
     * Kardex de un producto.
     *
     * `desde` y `hasta` son fechas civiles que el usuario elige mirando un
     * calendario de Lima, pero `created_at` es un instante en UTC: se
     * convierte el rango, no el dato. Sin eso, los movimientos de las últimas
     * cinco horas del día caerían fuera del día que la persona pidió.
     *
     * @return LengthAwarePaginator<int, MovimientoInventario>
     */
    public function kardex(int $productoId, ?string $desde = null, ?string $hasta = null, int $pagina = 1): LengthAwarePaginator
    {
        if (! Producto::query()->whereKey($productoId)->exists()) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El producto indicado no existe.',
                ['campo' => 'producto_id']
            );
        }

        $zona = config('app.timezone_visualizacion');
        $hasta ??= now()->timezone($zona)->format('Y-m-d');
        $desde ??= now()->timezone($zona)->subDays(self::DIAS_POR_DEFECTO)->format('Y-m-d');

        if ($desde > $hasta) {
            throw $this->fueraDeRango('La fecha inicial no puede ser posterior a la final.');
        }

        $inicio = Carbon::parse($desde.' 00:00:00', $zona)->utc();
        $fin = Carbon::parse($hasta.' 23:59:59.999999', $zona)->utc();

        if ($inicio->diffInDays($fin) > self::RANGO_MAXIMO_DIAS) {
            throw $this->fueraDeRango('El rango no puede superar los '.self::RANGO_MAXIMO_DIAS.' días.');
        }

        return MovimientoInventario::query()
            ->with(['lote', 'usuario'])
            ->where('producto_id', $productoId)
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarLote(Lote $lote, Usuario $actor, string $hoy): array
    {
        $presentado = [
            'id' => $lote->id,
            'codigo_lote' => $lote->codigo_lote,
            'fecha_vencimiento' => $lote->fecha_vencimiento->format('Y-m-d'),
            'cantidad_actual' => $lote->cantidad_actual,
            'vencido' => $lote->estaVencido($hoy),
        ];

        // El vendedor no ve el costo: es información de negociación con el
        // proveedor, no algo que necesite para vender (matriz de permisos).
        if ($actor->esAdministrador()) {
            $presentado['costo_unitario'] = $lote->costo_unitario;
        }

        return $presentado;
    }

    private function fueraDeRango(string $mensaje): ErrorDeDominio
    {
        return new ErrorDeDominio(CodigoDeError::CAMPO_FUERA_DE_RANGO, $mensaje);
    }
}
