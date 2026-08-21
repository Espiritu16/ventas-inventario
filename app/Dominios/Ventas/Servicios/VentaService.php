<?php

namespace App\Dominios\Ventas\Servicios;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Compartido\Fechas\RangoDeFechas;
use App\Compartido\Idempotencia\RegistroDeOperaciones;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Modelos\Comprobante;
use App\Dominios\Comprobantes\Modelos\SerieComprobante;
use App\Dominios\Comprobantes\Servicios\SerieComprobanteService;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Ventas\Modelos\DetalleVenta;
use App\Dominios\Ventas\Modelos\DetalleVentaLote;
use App\Dominios\Ventas\Modelos\Venta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de ventas (RF-011 a RF-014). La operación más delicada del sistema.
 *
 * Todo ocurre en una sola transacción: validar, descontar por vencimiento,
 * calcular IGV, reservar el correlativo y crear el comprobante. Si algo falla
 * no puede quedar ni venta, ni stock descontado, ni correlativo consumido —
 * cada uno de esos restos por separado es un problema distinto y ninguno se
 * arregla solo (RNF-003).
 *
 * El envío a SUNAT queda fuera: el comprobante nace PENDIENTE (ADR-0003).
 */
class VentaService
{
    private const POR_PAGINA = 20;

    private const MAXIMO_LINEAS = 100;

    /** IGV al 18 %, ya incluido en los precios (RNF-006). */
    private const FACTOR_IGV = '1.18';

    private const FORMATO_CANTIDAD = '/^\d{1,6}(\.\d{1,3})?$/';

    private const FORMATO_FECHA = '/^\d{4}-\d{2}-\d{2}$/';

    /** Un comprobante siempre existe; el rótulo evita que el desglose deje de sumar el total. */
    private const SIN_COMPROBANTE = 'SIN_COMPROBANTE';

    public function __construct(
        private readonly InventarioService $inventario,
        private readonly SerieComprobanteService $series,
        private readonly RegistroDeOperaciones $operaciones = new RegistroDeOperaciones,
    ) {}

    /**
     * Registra la venta una sola vez por clave de operación (RF-011).
     *
     * Es la puerta que debe usar la caja. Un doble clic o una recarga producen
     * dos peticiones idénticas, y una venta duplicada descuenta stock real y
     * consume un correlativo tributario: ninguna de las dos cosas se deshace
     * sola, y el cliente se llevó una sola bolsa.
     */
    public function registrarUnaSolaVez(string $claveDeOperacion, DatosDeEntrada $datos, Usuario $actor): Venta
    {
        return $this->operaciones->unaSolaVez(
            $claveDeOperacion,
            $datos->todos(),
            fn () => $this->registrar($datos, $actor),
        );
    }

    public function registrar(DatosDeEntrada $datos, Usuario $actor): Venta
    {
        $cabecera = $this->validarCabecera($datos);
        $lineas = $this->validarLineas($datos->valor('lineas'));

        $cliente = $this->clienteDisponible((int) $cabecera['cliente_id']);
        $preparadas = $this->prepararLineas($lineas);

        $total = '0.00';
        foreach ($preparadas as $linea) {
            $total = bcadd($total, $linea['importe'], 2);
        }

        $this->garantizarComprobanteAdmisible($cabecera['tipo_comprobante'], $cliente, $total);

        // base = total / 1,18 y IGV = total − base, en ese orden: derivar el
        // IGV por separado y sumarlo podría no dar el total por redondeo, y la
        // base de datos exige que la suma sea exacta.
        $subtotal = $this->redondear(bcdiv($total, self::FACTOR_IGV, 6), 2);
        $igv = bcsub($total, $subtotal, 2);

        return DB::transaction(function () use ($cabecera, $preparadas, $cliente, $actor, $subtotal, $igv, $total) {
            // El correlativo se reserva antes de tocar el stock: si la serie no
            // existe, la venta se rechaza sin haber descontado nada.
            $reserva = $this->series->reservarCorrelativo($cabecera['tipo_comprobante']);

            $venta = Venta::query()->create([
                'cliente_id' => $cliente->id,
                'usuario_id' => $actor->id,
                'fecha' => now(),
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'metodo_pago' => $cabecera['metodo_pago'],
            ]);

            foreach ($preparadas as $linea) {
                $detalle = DetalleVenta::query()->create([
                    'venta_id' => $venta->id,
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'tipo_precio' => $linea['tipo_precio'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'importe' => $linea['importe'],
                ]);

                $reparto = $this->inventario->descontarPorVencimiento(
                    productoId: (int) $linea['producto_id'],
                    cantidad: $linea['cantidad'],
                    origenTipo: MovimientoInventario::ORIGEN_VENTA,
                    origenId: (int) $venta->id,
                    usuarioId: (int) $actor->id,
                );

                foreach ($reparto as $porcion) {
                    DetalleVentaLote::query()->create([
                        'detalle_venta_id' => $detalle->id,
                        'lote_id' => $porcion['lote_id'],
                        'cantidad' => $porcion['cantidad'],
                        'costo_unitario' => $porcion['costo_unitario'],
                    ]);
                }
            }

            Comprobante::query()->create([
                'venta_id' => $venta->id,
                'serie_comprobante_id' => $reserva['serie']->id,
                'tipo_comprobante' => $cabecera['tipo_comprobante'],
                'serie' => $reserva['serie']->serie,
                'correlativo' => $reserva['correlativo'],
                'fecha_emision' => $this->fechaDeEmision(),
                'estado' => Comprobante::ESTADO_PENDIENTE,
            ]);

            return $venta->refresh();
        });
    }

    /**
     * Redondeo comercial, porque `bcdiv` trunca.
     *
     * Truncar parece inocuo con importes normales y no lo es en el borde: una
     * venta de S/ 0,01 daba base 0,00, y la base de datos exige base mayor que
     * cero porque un comprobante con base cero no es un comprobante válido.
     * Con importes grandes el mismo truncamiento se lleva céntimos que después
     * no cuadran contra el resumen diario.
     */
    private function redondear(string $valor, int $decimales): string
    {
        $mitad = '0.'.str_repeat('0', $decimales).'5';

        return bcadd($valor, $mitad, $decimales + 1) === $valor
            ? bcadd($valor, '0', $decimales)
            : bcadd($valor, $mitad, $decimales);
    }

    /**
     * Fecha civil en zona de Lima.
     *
     * Es un dato tributario: una venta cerrada a las 23:40 no puede quedar
     * emitida con la fecha del día siguiente, porque el resumen diario de
     * boletas se agrupa por este campo y partiría un día en dos.
     */
    private function fechaDeEmision(): string
    {
        return now()->timezone(config('app.timezone_visualizacion'))->format('Y-m-d');
    }

    /** @return LengthAwarePaginator<int, Venta> */
    public function listar(
        Usuario $actor,
        ?string $desde = null,
        ?string $hasta = null,
        ?string $estadoComprobante = null,
        int $pagina = 1,
    ): LengthAwarePaginator {
        return Venta::query()
            ->with('comprobante')
            // El vendedor solo ve lo suyo, y el acote entra en la consulta —no
            // se filtra después— para que también acote el total del paginado.
            ->when(! $actor->esAdministrador(), fn ($c) => $c->where('usuario_id', $actor->id))
            ->when($desde !== null, fn ($c) => $c->where('fecha', '>=', $this->inicioDelDia($desde)))
            ->when($hasta !== null, fn ($c) => $c->where('fecha', '<=', $this->finDelDia($hasta)))
            ->when($estadoComprobante !== null, fn ($c) => $c->whereHas('comprobante', fn ($q) => $q->where('estado', $estadoComprobante)))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    /**
     * Una venta ajena responde igual que una inexistente: distinguirlas le
     * diría al vendedor que existe una venta que no puede ver, y con eso el
     * identificador se vuelve un canal para averiguar cuántas hay.
     *
     * Devuelve la proyección, no el modelo: quién puede pedir la venta y qué
     * campos viajan dentro son dos preguntas distintas, y el alcance de arriba
     * solo responde la primera.
     *
     * @return array<string, mixed>
     */
    public function encontrar(int $id, Usuario $actor): array
    {
        $venta = Venta::query()
            ->with(['lineas.reparto.lote', 'lineas.producto', 'comprobante', 'cliente'])
            ->when(! $actor->esAdministrador(), fn ($c) => $c->where('usuario_id', $actor->id))
            ->find($id);

        if ($venta === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'La venta indicada no existe.'
            );
        }

        return $this->presentar($venta, $actor);
    }

    /**
     * Enumera lo que sale, en vez de quitar lo que no debe salir.
     *
     * La diferencia importa el día que aparezca un campo nuevo: partir del
     * modelo entero y descartar el costo deja pasar lo próximo que se agregue
     * sin que nadie lo note, mientras que una lista de campos deja fuera lo
     * desconocido por construcción. Es la forma que ya se usa en el stock por
     * lote.
     *
     * @return array<string, mixed>
     */
    private function presentar(Venta $venta, Usuario $actor): array
    {
        return [
            'id' => $venta->id,
            'fecha' => $venta->fecha->toIso8601String(),
            'subtotal' => $venta->subtotal,
            'igv' => $venta->igv,
            'total' => $venta->total,
            'metodo_pago' => $venta->metodo_pago,
            'cliente' => $venta->cliente === null ? null : [
                'id' => $venta->cliente->id,
                'tipo_documento' => $venta->cliente->tipo_documento,
                'numero_documento' => $venta->cliente->numero_documento,
                'nombre' => $venta->cliente->nombre,
            ],
            'comprobante' => $venta->comprobante === null ? null : [
                'tipo_comprobante' => $venta->comprobante->tipo_comprobante,
                'serie' => $venta->comprobante->serie,
                'correlativo' => $venta->comprobante->correlativo,
                'fecha_emision' => $venta->comprobante->fecha_emision->format('Y-m-d'),
                'estado' => $venta->comprobante->estado,
            ],
            'lineas' => $venta->lineas->map(fn (DetalleVenta $linea) => [
                'id' => $linea->id,
                'producto' => [
                    'id' => $linea->producto->id,
                    'codigo' => $linea->producto->codigo,
                    'nombre' => $linea->producto->nombre,
                ],
                'cantidad' => $linea->cantidad,
                'tipo_precio' => $linea->tipo_precio,
                'precio_unitario' => $linea->precio_unitario,
                'importe' => $linea->importe,
                'reparto' => $linea->reparto
                    ->map(fn (DetalleVentaLote $porcion) => $this->presentarPorcion($porcion, $actor))
                    ->all(),
            ])->all(),
        ];
    }

    /**
     * El costo aparece en dos sitios sobre la misma porción —en el reparto y
     * en el lote del que salió—, así que ocultarlo en uno solo no lo oculta.
     *
     * @return array<string, mixed>
     */
    private function presentarPorcion(DetalleVentaLote $porcion, Usuario $actor): array
    {
        $presentada = [
            'lote_id' => $porcion->lote_id,
            'codigo_lote' => $porcion->lote?->codigo_lote,
            'fecha_vencimiento' => $porcion->lote?->fecha_vencimiento?->format('Y-m-d'),
            'cantidad' => $porcion->cantidad,
        ];

        // El costo es información de negociación con el proveedor, no algo que
        // quien vende necesite: con el precio de venta al lado, el margen sale
        // por resta. El sistema ya lo acota en catálogo y en inventario, y el
        // reporte de utilidad es solo de administrador (contrato de ventas,
        // enmienda de Arquitectura 2026-08-20).
        if ($actor->esAdministrador()) {
            $presentada['costo_unitario'] = $porcion->costo_unitario;
            $presentada['costo_unitario_lote'] = $porcion->lote?->costo_unitario;
        }

        return $presentada;
    }

    /**
     * Ventas del período con su desglose (RF-020).
     *
     * El total es la suma de **todas** las ventas del rango, incluidas aquellas
     * cuyo comprobante rechazó SUNAT: la venta ocurrió y el dinero entró; lo
     * que quedó mal es el documento. Por eso el rechazo se **señala** —por
     * venta y con su propio subtotal— en vez de descontarse del total, que es
     * lo que pide RF-020 al decir que las distingue.
     *
     * Los desgloses se arman recorriendo las mismas ventas que se totalizan, y
     * no con consultas agregadas aparte: así suman el total por construcción y
     * no porque dos consultas coincidan.
     *
     * @return array<string, mixed>
     */
    public function reporteVentas(string $desde, string $hasta): array
    {
        [$inicio, $fin] = $this->rangoDelReporte($desde, $hasta);

        $ventas = Venta::query()
            ->with(['comprobante', 'cliente'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $total = '0.00';
        $porComprobante = [];
        $porMetodoPago = [];
        $rechazadas = ['cantidad' => 0, 'total' => '0.00'];
        $detalle = [];

        foreach ($ventas as $venta) {
            $tipo = $venta->comprobante?->tipo_comprobante ?? self::SIN_COMPROBANTE;
            $rechazado = $venta->comprobante?->estado === Comprobante::ESTADO_RECHAZADO;

            $total = bcadd($total, $venta->total, 2);
            $porComprobante[$tipo] = $this->acumular($porComprobante[$tipo] ?? null, $venta->total);
            $porMetodoPago[$venta->metodo_pago] = $this->acumular($porMetodoPago[$venta->metodo_pago] ?? null, $venta->total);

            if ($rechazado) {
                $rechazadas = $this->acumular($rechazadas, $venta->total);
            }

            $detalle[] = [
                'id' => $venta->id,
                'fecha' => $venta->fecha->toIso8601String(),
                'cliente' => $venta->cliente?->nombre,
                'metodo_pago' => $venta->metodo_pago,
                'total' => $venta->total,
                'comprobante' => $venta->comprobante === null ? null : [
                    'tipo_comprobante' => $venta->comprobante->tipo_comprobante,
                    'serie' => $venta->comprobante->serie,
                    'correlativo' => $venta->comprobante->correlativo,
                    'estado' => $venta->comprobante->estado,
                ],
                'rechazado' => $rechazado,
            ];
        }

        ksort($porComprobante);
        ksort($porMetodoPago);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'cantidad' => $ventas->count(),
            'total' => $total,
            'por_comprobante' => $this->desglose($porComprobante, 'tipo_comprobante'),
            'por_metodo_pago' => $this->desglose($porMetodoPago, 'metodo_pago'),
            'rechazadas' => $rechazadas,
            'ventas' => $detalle,
        ];
    }

    /**
     * Utilidad real del período: ingreso menos el costo de las porciones de
     * lote que efectivamente salieron (RF-021, ADR-0004).
     *
     * **El costo no puede salir de la misma consulta que el ingreso.** Una
     * línea de venta se cubre con una o varias porciones de lote, así que unir
     * `detalle_ventas` con `detalle_venta_lotes` multiplica la línea por sus
     * porciones y el importe se contaría dos veces. Son dos agregados sobre
     * granularidades distintas y se calculan por separado.
     *
     * El costo sale de `detalle_venta_lotes`, que congeló el costo del lote al
     * momento de la salida: ni un promedio del producto, ni el costo que el
     * lote tenga hoy.
     *
     * @return array<string, mixed>
     */
    public function reporteUtilidad(string $desde, string $hasta, ?int $productoId = null): array
    {
        [$inicio, $fin] = $this->rangoDelReporte($desde, $hasta);

        $ingresos = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->when($productoId !== null, fn ($c) => $c->where('detalle_ventas.producto_id', $productoId))
            ->groupBy('detalle_ventas.producto_id')
            ->selectRaw('detalle_ventas.producto_id as producto_id')
            ->selectRaw('sum(detalle_ventas.cantidad) as cantidad')
            ->selectRaw('sum(detalle_ventas.importe) as ingreso')
            ->get()
            ->keyBy('producto_id');

        $costos = DetalleVentaLote::query()
            ->join('detalle_ventas', 'detalle_ventas.id', '=', 'detalle_venta_lotes.detalle_venta_id')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->when($productoId !== null, fn ($c) => $c->where('detalle_ventas.producto_id', $productoId))
            ->groupBy('detalle_ventas.producto_id')
            ->selectRaw('detalle_ventas.producto_id as producto_id')
            ->selectRaw('sum(detalle_venta_lotes.cantidad * detalle_venta_lotes.costo_unitario) as costo')
            ->get()
            ->keyBy('producto_id');

        $productos = Producto::query()
            ->whereIn('id', $ingresos->keys()->all())
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();

        $porProducto = [];
        $ingresoTotal = '0.00';
        $costoTotal = '0.00';

        foreach ($productos as $producto) {
            $fila = $ingresos->get($producto->id);
            $ingreso = bcadd((string) $fila->ingreso, '0', 2);
            // Se redondea por producto y el total se suma de esas partes: un
            // reporte cuyas filas no suman su propio total es un reporte que
            // quien lo lee deja de creer.
            $costo = $this->redondear((string) ($costos->get($producto->id)?->costo ?? '0'), 2);

            $porProducto[] = [
                'producto_id' => (int) $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'cantidad' => bcadd((string) $fila->cantidad, '0', 3),
                'ingreso' => $ingreso,
                'costo' => $costo,
                'utilidad' => bcsub($ingreso, $costo, 2),
            ];

            $ingresoTotal = bcadd($ingresoTotal, $ingreso, 2);
            $costoTotal = bcadd($costoTotal, $costo, 2);
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'ingreso' => $ingresoTotal,
            'costo' => $costoTotal,
            'utilidad' => bcsub($ingresoTotal, $costoTotal, 2),
            'por_producto' => $porProducto,
        ];
    }

    /**
     * @param  array{cantidad: int, total: string}|null  $acumulado
     * @return array{cantidad: int, total: string}
     */
    private function acumular(?array $acumulado, string $importe): array
    {
        $acumulado ??= ['cantidad' => 0, 'total' => '0.00'];

        return [
            'cantidad' => $acumulado['cantidad'] + 1,
            'total' => bcadd($acumulado['total'], $importe, 2),
        ];
    }

    /**
     * Pasa el acumulador —indexado por su clave para poder sumarlo— a una
     * lista con la clave adentro, que es lo que una pantalla recorre.
     *
     * @param  array<string, array{cantidad: int, total: string}>  $acumulado
     * @return array<int, array<string, mixed>>
     */
    private function desglose(array $acumulado, string $nombreDeLaClave): array
    {
        $filas = [];

        foreach ($acumulado as $clave => $valores) {
            $filas[] = [$nombreDeLaClave => $clave] + $valores;
        }

        return $filas;
    }

    /**
     * Rango del reporte, como instantes UTC del día civil de Lima.
     *
     * Las dos fechas son las que alguien elige mirando un calendario, pero
     * `ventas.fecha` es un instante: se convierte el rango y no el dato. Sin
     * eso, una venta de las 20:00 del último día del rango —ya del día
     * siguiente en UTC— quedaría fuera del reporte que la incluye.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoDelReporte(string $desde, string $hasta): array
    {
        foreach (['desde' => $desde, 'hasta' => $hasta] as $campo => $valor) {
            if (trim($valor) === '') {
                throw new ErrorDeDominio(
                    CodigoDeError::CAMPO_REQUERIDO,
                    'El reporte necesita las dos fechas del período.',
                    ['campo' => $campo]
                );
            }

            if (! $this->esFechaDeCalendario($valor)) {
                throw new ErrorDeDominio(
                    CodigoDeError::CAMPO_FORMATO_INVALIDO,
                    'La fecha debe escribirse como AAAA-MM-DD.',
                    ['campo' => $campo]
                );
            }
        }

        if ($desde > $hasta) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FUERA_DE_RANGO,
                'La fecha inicial no puede ser posterior a la final.',
                ['campo' => 'desde']
            );
        }

        $inicio = $this->inicioDelDia($desde);
        $fin = $this->finDelDia($hasta);

        if ($inicio->diffInDays($fin) > RangoDeFechas::MAXIMO_DIAS) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FUERA_DE_RANGO,
                'El rango no puede superar los '.RangoDeFechas::MAXIMO_DIAS.' días.',
                ['campo' => 'hasta']
            );
        }

        return [$inicio, $fin];
    }

    /**
     * Formato y existencia: `2026-02-30` cumple el formato y no es un día.
     */
    private function esFechaDeCalendario(string $valor): bool
    {
        if (preg_match(self::FORMATO_FECHA, $valor) !== 1) {
            return false;
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $valor));

        return checkdate($mes, $dia, $anio);
    }

    /** @return array<string, mixed> */
    private function validarCabecera(DatosDeEntrada $datos): array
    {
        return ValidadorDeDominio::validar(
            array_intersect_key($datos->todos(), array_flip(['cliente_id', 'tipo_comprobante', 'metodo_pago'])),
            [
                'cliente_id' => ['required', 'integer', 'min:1'],
                'tipo_comprobante' => ['required', 'string', 'in:'.SerieComprobante::TIPO_FACTURA.','.SerieComprobante::TIPO_BOLETA],
                'metodo_pago' => ['required', 'string', 'in:'.implode(',', Venta::METODOS_PAGO)],
            ]
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function validarLineas(mixed $lineas): array
    {
        if (! is_array($lineas) || $lineas === []) {
            throw new ErrorDeDominio(
                CodigoDeError::VENTA_SIN_LINEAS,
                'Una venta necesita al menos una línea.',
                ['campo' => 'lineas']
            );
        }

        if (count($lineas) > self::MAXIMO_LINEAS) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FUERA_DE_RANGO,
                'Una venta admite hasta '.self::MAXIMO_LINEAS.' líneas.',
                ['campo' => 'lineas']
            );
        }

        $validadas = [];

        foreach ($lineas as $linea) {
            $linea = is_array($linea) ? $linea : [];

            foreach (['cantidad'] as $campo) {
                if (isset($linea[$campo]) && (is_int($linea[$campo]) || is_float($linea[$campo]))) {
                    $linea[$campo] = (string) $linea[$campo];
                }
            }

            $validadas[] = ValidadorDeDominio::validar(
                array_intersect_key($linea, array_flip(['producto_id', 'cantidad', 'tipo_precio'])),
                [
                    'producto_id' => ['required', 'integer', 'min:1'],
                    'cantidad' => ['required', 'regex:'.self::FORMATO_CANTIDAD],
                    'tipo_precio' => ['required', 'string'],
                ]
            );
        }

        return $validadas;
    }

    /**
     * Copia el precio del producto según el tipo elegido y calcula el importe.
     *
     * El precio no se acepta de la entrada: si viniera de afuera, quien opera
     * la caja podría vender a cualquier valor y el catálogo dejaría de decidir
     * el precio.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     * @return array<int, array<string, mixed>>
     */
    private function prepararLineas(array $lineas): array
    {
        $preparadas = [];

        foreach ($lineas as $linea) {
            if (! in_array($linea['tipo_precio'], DetalleVenta::TIPOS_PRECIO, true)) {
                throw new ErrorDeDominio(
                    CodigoDeError::TIPO_PRECIO_INVALIDO,
                    'El tipo de precio debe ser menor o mayor.',
                    ['campo' => 'tipo_precio']
                );
            }

            $producto = Producto::query()->find($linea['producto_id']);

            if ($producto === null) {
                throw new ErrorDeDominio(
                    CodigoDeError::RECURSO_NO_ENCONTRADO,
                    'El producto indicado no existe.',
                    ['campo' => 'producto_id']
                );
            }

            if (! $producto->activo) {
                throw new ErrorDeDominio(
                    CodigoDeError::PRODUCTO_INACTIVO,
                    'No se puede vender un producto desactivado.',
                    ['campo' => 'producto_id']
                );
            }

            $precio = $linea['tipo_precio'] === 'menor' ? $producto->precio_menor : $producto->precio_mayor;

            $preparadas[] = $linea + [
                'precio_unitario' => $precio,
                'importe' => bcmul($linea['cantidad'], $precio, 2),
            ];
        }

        return $preparadas;
    }

    private function clienteDisponible(int $clienteId): Cliente
    {
        $cliente = Cliente::query()->find($clienteId);

        if ($cliente === null || ! $cliente->activo) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El cliente indicado no existe o está desactivado.',
                ['campo' => 'cliente_id']
            );
        }

        return $cliente;
    }

    /**
     * Reglas del momento de emitir, no del alta del cliente: al darlo de alta
     * no se sabe si recibirá factura o boleta.
     */
    private function garantizarComprobanteAdmisible(string $tipo, Cliente $cliente, string $total): void
    {
        if ($tipo === SerieComprobante::TIPO_FACTURA) {
            if ($cliente->tipoDeDocumento() !== TipoDeDocumento::RUC) {
                throw new ErrorDeDominio(
                    CodigoDeError::FACTURA_REQUIERE_RUC,
                    'Una factura exige que el cliente tenga RUC.',
                    ['campo' => 'tipo_comprobante']
                );
            }

            if ($cliente->direccion === null || trim($cliente->direccion) === '') {
                throw new ErrorDeDominio(
                    CodigoDeError::FACTURA_REQUIERE_RUC,
                    'Una factura exige que el cliente tenga dirección: viaja en el comprobante.',
                    ['campo' => 'direccion']
                );
            }

            return;
        }

        // Boleta: sobre el tope hay que identificar al cliente. El tope es
        // configurable para poder ajustarlo si cambia la norma sin tocar código.
        $tope = (string) config('venta.tope_boleta_sin_documento');

        if (bccomp($total, $tope, 2) > 0 && ! $cliente->tieneDocumento()) {
            throw new ErrorDeDominio(
                CodigoDeError::BOLETA_REQUIERE_DOCUMENTO,
                "Una boleta por más de S/ {$tope} exige identificar al cliente.",
                ['campo' => 'cliente_id', 'tope' => $tope]
            );
        }
    }

    private function inicioDelDia(string $fecha): Carbon
    {
        return Carbon::parse($fecha.' 00:00:00', config('app.timezone_visualizacion'))->utc();
    }

    private function finDelDia(string $fecha): Carbon
    {
        return Carbon::parse($fecha.' 23:59:59.999999', config('app.timezone_visualizacion'))->utc();
    }
}
