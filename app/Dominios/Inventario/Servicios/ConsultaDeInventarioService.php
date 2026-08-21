<?php

namespace App\Dominios\Inventario\Servicios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Fechas\RangoDeFechas;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lecturas de inventario y kardex (RF-007, RF-008), y las alertas del tablero
 * (RF-018, RF-019).
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

    /** Plazo de la alerta de vencimiento, el que fija el contrato de `GET /panel`. */
    public const DIAS_POR_VENCER_POR_DEFECTO = 30;

    /**
     * Extremos del plazo admisible, también del contrato de `GET /panel` (de 1
     * a 365). Son públicos porque quien construye el formulario y quien prueba
     * el borde necesitan el mismo número: escribirlo a mano del otro lado es
     * volver a tener dos valores que alguien debe mantener iguales.
     */
    public const DIAS_POR_VENCER_MINIMO = 1;

    public const DIAS_POR_VENCER_MAXIMO = 365;

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

        if ($inicio->diffInDays($fin) > RangoDeFechas::MAXIMO_DIAS) {
            throw $this->fueraDeRango('El rango no puede superar los '.RangoDeFechas::MAXIMO_DIAS.' días.');
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
     * Lotes que vencen dentro del plazo y los ya vencidos con existencia, en
     * orden de urgencia (RF-018).
     *
     * Los vencidos y los por vencer salen de **una sola condición**: un lote
     * vencido es uno cuyo vencimiento ya pasó, así que `fecha_vencimiento <=
     * hoy + plazo` los abarca a los dos. Separarlos en dos consultas y unirlas
     * dejaría dos criterios que mantener iguales.
     *
     * El orden por vencimiento ascendente *es* el orden de urgencia: primero
     * lo que venció hace más tiempo, después lo que vence antes.
     *
     * **No lleva costo.** El tablero lo ve también el vendedor, y la matriz de
     * permisos lo declara «sin indicadores de utilidad»; como este método no
     * recibe actor, no puede proyectar por rol y por lo tanto no puede traer un
     * dato que a la mitad de sus lectores no le corresponde.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lotesPorVencer(int $dias = self::DIAS_POR_VENCER_POR_DEFECTO): Collection
    {
        if ($dias < self::DIAS_POR_VENCER_MINIMO || $dias > self::DIAS_POR_VENCER_MAXIMO) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FUERA_DE_RANGO,
                'El plazo de la alerta debe estar entre '
                    .self::DIAS_POR_VENCER_MINIMO.' y '.self::DIAS_POR_VENCER_MAXIMO.' días.',
                ['campo' => 'dias']
            );
        }

        // El mismo `hoy` que usa `stockDisponible`, para que lo que la alerta
        // llama vencido sea exactamente lo que el disponible deja fuera.
        $hoy = now()->format('Y-m-d');
        $limite = Carbon::parse($hoy)->addDays($dias)->format('Y-m-d');

        return Lote::query()
            ->with('producto')
            ->where('cantidad_actual', '>', 0)
            ->where('fecha_vencimiento', '<=', $limite)
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get()
            ->map(fn (Lote $lote) => [
                'id' => $lote->id,
                'producto_id' => $lote->producto_id,
                'producto_codigo' => $lote->producto?->codigo,
                'producto_nombre' => $lote->producto?->nombre,
                'codigo_lote' => $lote->codigo_lote,
                'fecha_vencimiento' => $lote->fecha_vencimiento->format('Y-m-d'),
                'cantidad_actual' => $lote->cantidad_actual,
                'vencido' => $lote->estaVencido($hoy),
                'dias_para_vencer' => (int) Carbon::parse($hoy)
                    ->diffInDays(Carbon::parse($lote->fecha_vencimiento->format('Y-m-d')), false),
            ])
            ->values();
    }

    /**
     * Productos activos cuyo stock disponible quedó en su mínimo o por debajo
     * (RF-019).
     *
     * El disponible se calcula igual que en el resto del inventario —lotes no
     * vencidos con existencia—, así que un producto cuyas únicas unidades
     * están vencidas aparece con disponible cero: físicamente hay mercadería y
     * vendible no hay ninguna, que es justo lo que la alerta tiene que decir.
     *
     * Se resuelve con una sola consulta agregada y no producto por producto:
     * el tablero se abre en cada carga y el catálogo crece.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function productosBajoMinimo(): Collection
    {
        $hoy = now()->format('Y-m-d');

        return Producto::query()
            ->leftJoin('lotes', function ($union) use ($hoy) {
                $union->on('lotes.producto_id', '=', 'productos.id')
                    ->where('lotes.fecha_vencimiento', '>=', $hoy)
                    ->where('lotes.cantidad_actual', '>', 0);
            })
            ->where('productos.activo', true)
            ->groupBy('productos.id')
            ->havingRaw('coalesce(sum(lotes.cantidad_actual), 0) <= productos.stock_minimo')
            ->orderBy('productos.nombre')
            ->orderBy('productos.id')
            ->select('productos.*')
            ->selectRaw('coalesce(sum(lotes.cantidad_actual), 0) as disponible')
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'unidad_medida' => $producto->unidad_medida,
                'stock_disponible' => bcadd((string) $producto->getAttribute('disponible'), '0', 3),
                'stock_minimo' => bcadd($producto->stock_minimo, '0', 3),
            ])
            ->values();
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
