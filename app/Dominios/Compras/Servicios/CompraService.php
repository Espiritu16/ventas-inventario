<?php

namespace App\Dominios\Compras\Servicios;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Compras\Modelos\Compra;
use App\Dominios\Compras\Modelos\DetalleCompra;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Proveedores\Modelos\Proveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Registro de compras (RF-006, contrato docs/contratos/compras.md v1).
 *
 * Es la única operación que crea existencias: todo lote nace acá.
 *
 * La compra, los lotes y los movimientos se escriben en **una sola
 * transacción**. Si algo falla a mitad no puede quedar la compra sin stock ni
 * el stock sin compra: el inventario dejaría de cuadrar con los documentos y
 * nadie sabría cuál de los dos tiene razón (RNF-003).
 */
class CompraService
{
    private const POR_PAGINA = 20;

    private const MAXIMO_LINEAS = 200;

    private const DIAS_HACIA_ATRAS = 365;

    private const FORMATO_CANTIDAD = '/^\d{1,6}(\.\d{1,3})?$/';

    private const FORMATO_COSTO = '/^\d{1,8}(\.\d{1,4})?$/';

    public function __construct(private readonly InventarioService $inventario) {}

    public function registrar(DatosDeEntrada $datos, int $usuarioId): Compra
    {
        $cabecera = $this->validarCabecera($datos);
        $lineas = $this->validarLineas($datos->valor('lineas'));

        $proveedor = Proveedor::query()->find($cabecera['proveedor_id']);

        if ($proveedor === null || ! $proveedor->activo) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El proveedor indicado no existe o está desactivado.',
                ['campo' => 'proveedor_id']
            );
        }

        $this->garantizarDocumentoLibre($cabecera);

        // El total lo calcula el sistema: aceptar uno enviado permitiría que
        // el documento diga una cosa y las líneas otra.
        $total = '0.00';
        foreach ($lineas as $linea) {
            $total = bcadd($total, bcmul($linea['cantidad'], $linea['costo_unitario'], 4), 2);
        }

        return DB::transaction(function () use ($cabecera, $lineas, $total, $usuarioId) {
            $compra = Compra::query()->create($cabecera + [
                'total' => $total,
                'usuario_id' => $usuarioId,
            ]);

            foreach ($lineas as $linea) {
                $lote = $this->inventario->ingresar(
                    productoId: (int) $linea['producto_id'],
                    cantidad: $linea['cantidad'],
                    costoUnitario: $linea['costo_unitario'],
                    codigoLote: $linea['codigo_lote'],
                    fechaVencimiento: $linea['fecha_vencimiento'],
                    origenTipo: MovimientoInventario::ORIGEN_COMPRA,
                    origenId: (int) $compra->id,
                    usuarioId: $usuarioId,
                );

                DetalleCompra::query()->create($linea + [
                    'compra_id' => $compra->id,
                    'lote_id' => $lote->id,
                ]);
            }

            return $compra->refresh();
        });
    }

    /** @return LengthAwarePaginator<int, Compra> */
    public function listar(
        ?string $desde = null,
        ?string $hasta = null,
        ?int $proveedorId = null,
        int $pagina = 1,
    ): LengthAwarePaginator {
        return Compra::query()
            ->when($desde !== null, fn ($c) => $c->where('fecha_emision', '>=', $desde))
            ->when($hasta !== null, fn ($c) => $c->where('fecha_emision', '<=', $hasta))
            ->when($proveedorId !== null, fn ($c) => $c->where('proveedor_id', $proveedorId))
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    public function encontrar(int $id): Compra
    {
        $compra = Compra::query()->with(['lineas.lote', 'lineas.producto', 'proveedor'])->find($id);

        if ($compra === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'La compra indicada no existe.'
            );
        }

        return $compra;
    }

    /** @return array<string, mixed> */
    private function validarCabecera(DatosDeEntrada $datos): array
    {
        $campos = ValidadorDeDominio::validar(
            $this->normalizarCabecera($datos->todos()),
            [
                'proveedor_id' => ['required', 'integer', 'min:1'],
                'tipo_documento' => ['required', 'string', 'in:'.implode(',', Compra::TIPOS_DOCUMENTO)],
                'serie_documento' => ['required', 'string', 'between:1,4', 'regex:/^[A-Z0-9]+$/'],
                'numero_documento' => ['required', 'string', 'between:1,8', 'regex:/^[0-9]+$/'],
                'fecha_emision' => ['required', 'string', 'date_format:Y-m-d'],
            ]
        );

        $hoy = now()->format('Y-m-d');

        if ($campos['fecha_emision'] > $hoy) {
            throw $this->fueraDeRango('La fecha de emisión no puede ser futura.', 'fecha_emision');
        }

        if ($campos['fecha_emision'] < now()->subDays(self::DIAS_HACIA_ATRAS)->format('Y-m-d')) {
            throw $this->fueraDeRango('La fecha de emisión es demasiado antigua.', 'fecha_emision');
        }

        return $campos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validarLineas(mixed $lineas): array
    {
        if (! is_array($lineas) || $lineas === []) {
            throw new ErrorDeDominio(
                CodigoDeError::COMPRA_SIN_LINEAS,
                'Una compra necesita al menos una línea.',
                ['campo' => 'lineas']
            );
        }

        if (count($lineas) > self::MAXIMO_LINEAS) {
            throw $this->fueraDeRango('Una compra admite hasta '.self::MAXIMO_LINEAS.' líneas.', 'lineas');
        }

        $validadas = [];

        foreach ($lineas as $linea) {
            $validadas[] = ValidadorDeDominio::validar(
                $this->normalizarLinea(is_array($linea) ? $linea : []),
                [
                    'producto_id' => ['required', 'integer', 'min:1'],
                    'cantidad' => ['required', 'regex:'.self::FORMATO_CANTIDAD],
                    'costo_unitario' => ['required', 'regex:'.self::FORMATO_COSTO],
                    'codigo_lote' => ['required', 'string', 'between:1,40', 'regex:/^[A-Z0-9.\-]+$/'],
                    'fecha_vencimiento' => ['required', 'string', 'date_format:Y-m-d'],
                ]
            );
        }

        foreach ($validadas as $linea) {
            if (bccomp($linea['cantidad'], '0', 3) <= 0) {
                throw $this->fueraDeRango('La cantidad de una línea debe ser mayor que cero.', 'cantidad');
            }

            if (bccomp($linea['costo_unitario'], '0', 4) <= 0) {
                throw $this->fueraDeRango('El costo unitario debe ser mayor que cero.', 'costo_unitario');
            }
        }

        return $validadas;
    }

    /** @param  array<string, mixed>  $cabecera */
    private function garantizarDocumentoLibre(array $cabecera): void
    {
        $existe = Compra::query()
            ->where('proveedor_id', $cabecera['proveedor_id'])
            ->where('tipo_documento', $cabecera['tipo_documento'])
            ->where('serie_documento', $cabecera['serie_documento'])
            ->where('numero_documento', $cabecera['numero_documento'])
            ->exists();

        if ($existe) {
            throw new ErrorDeDominio(
                CodigoDeError::COMPRA_DOCUMENTO_DUPLICADO,
                'Ese documento ya se registró para este proveedor.',
                ['campo' => 'numero_documento']
            );
        }
    }

    private function fueraDeRango(string $mensaje, string $campo): ErrorDeDominio
    {
        return new ErrorDeDominio(CodigoDeError::CAMPO_FUERA_DE_RANGO, $mensaje, ['campo' => $campo]);
    }

    /**
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function normalizarCabecera(array $campos): array
    {
        foreach (['tipo_documento', 'serie_documento'] as $campo) {
            if (isset($campos[$campo]) && is_string($campos[$campo])) {
                $campos[$campo] = mb_strtoupper(trim($campos[$campo]));
            }
        }

        // Los ceros a la izquierda se conservan: el número del documento es
        // una cadena impresa, no un entero.
        if (isset($campos['numero_documento'])) {
            $campos['numero_documento'] = trim((string) $campos['numero_documento']);
        }

        return array_intersect_key($campos, array_flip([
            'proveedor_id', 'tipo_documento', 'serie_documento', 'numero_documento', 'fecha_emision',
        ]));
    }

    /**
     * @param  array<string, mixed>  $linea
     * @return array<string, mixed>
     */
    private function normalizarLinea(array $linea): array
    {
        if (isset($linea['codigo_lote']) && is_string($linea['codigo_lote'])) {
            $linea['codigo_lote'] = mb_strtoupper(trim($linea['codigo_lote']));
        }

        foreach (['cantidad', 'costo_unitario'] as $campo) {
            if (isset($linea[$campo]) && (is_int($linea[$campo]) || is_float($linea[$campo]))) {
                $linea[$campo] = (string) $linea[$campo];
            }
        }

        return array_intersect_key($linea, array_flip([
            'producto_id', 'cantidad', 'costo_unitario', 'codigo_lote', 'fecha_vencimiento',
        ]));
    }
}
