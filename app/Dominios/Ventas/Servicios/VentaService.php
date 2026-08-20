<?php

namespace App\Dominios\Ventas\Servicios;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
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
     */
    public function encontrar(int $id, Usuario $actor): Venta
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

        return $venta;
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
                ],
                ['tipo_precio' => CodigoDeError::TIPO_PRECIO_INVALIDO]
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
