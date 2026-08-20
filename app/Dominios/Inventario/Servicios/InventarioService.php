<?php

namespace App\Dominios\Inventario\Servicios;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Auditoria\Modelos\Auditoria;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Única puerta de escritura del stock (ADR-0004).
 *
 * Ninguna otra clase toca `lotes` ni el kardex: si el saldo del lote y su
 * historial se escribieran por separado, podrían divergir, y el saldo es lo
 * que decide si una venta se puede cobrar. Una prueba de arquitectura lo
 * verifica.
 *
 * Toda escritura ocurre dentro de una transacción y bloqueando la fila del
 * lote: un ajuste y una venta simultáneos sobre el mismo lote se pisarían, y
 * el daño sería invisible hasta que el stock no cuadre (RNF-003).
 */
class InventarioService
{
    /** Motivos admitidos para un ajuste manual (RF-009). */
    public const MOTIVOS = ['merma', 'rotura', 'vencimiento', 'error_conteo'];

    public function __construct(private readonly AuditoriaService $auditoria) {}

    /**
     * Ingresa mercadería: acumula sobre el lote existente si producto, código,
     * vencimiento y costo coinciden, y crea uno nuevo si no.
     *
     * Un costo distinto abre lote aparte a propósito: es lo que permite
     * calcular después la utilidad real con el costo del lote del que salió
     * cada porción, y no con un promedio (RF-021).
     */
    public function ingresar(
        int $productoId,
        string $cantidad,
        string $costoUnitario,
        string $codigoLote,
        string $fechaVencimiento,
        string $origenTipo,
        int $origenId,
        int $usuarioId,
    ): Lote {
        $producto = $this->productoDisponible($productoId);
        $this->garantizarVencimientoFuturo($fechaVencimiento);

        return DB::transaction(function () use (
            $producto, $cantidad, $costoUnitario, $codigoLote,
            $fechaVencimiento, $origenTipo, $origenId, $usuarioId
        ) {
            $lote = Lote::query()
                ->where('producto_id', $producto->id)
                ->where('codigo_lote', $codigoLote)
                ->where('fecha_vencimiento', $fechaVencimiento)
                ->where('costo_unitario', $costoUnitario)
                ->lockForUpdate()
                ->first();

            if ($lote === null) {
                $lote = Lote::query()->create([
                    'producto_id' => $producto->id,
                    'codigo_lote' => $codigoLote,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'cantidad_actual' => $cantidad,
                    'costo_unitario' => $costoUnitario,
                ]);
            } else {
                $lote->cantidad_actual = bcadd($lote->cantidad_actual, $cantidad, 3);
                $lote->save();
            }

            $this->registrarMovimiento(
                $lote, MovimientoInventario::TIPO_INGRESO, $cantidad,
                $costoUnitario, $origenTipo, $origenId, $usuarioId
            );

            return $lote->refresh();
        });
    }

    /**
     * Ajuste manual con motivo obligatorio (RF-009).
     *
     * Lo que se registra en el kardex es la **diferencia** contra la cantidad
     * actual, no la cantidad nueva: el historial cuenta qué cambió, y sumarlo
     * tiene que dar el saldo.
     */
    public function ajustar(
        int $loteId,
        string $cantidadNueva,
        string $motivo,
        ?string $observacion,
        int $usuarioId,
    ): Lote {
        if (! in_array($motivo, self::MOTIVOS, true)) {
            throw new ErrorDeDominio(
                CodigoDeError::AJUSTE_SIN_MOTIVO,
                'El ajuste necesita un motivo de los admitidos.',
                ['campo' => 'motivo']
            );
        }

        if ($motivo === 'error_conteo' && ($observacion === null || trim($observacion) === '')) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_REQUERIDO,
                'Un ajuste por error de conteo necesita una observación que lo explique.',
                ['campo' => 'observacion']
            );
        }

        if (bccomp($cantidadNueva, '0', 3) < 0) {
            throw new ErrorDeDominio(
                CodigoDeError::AJUSTE_CANTIDAD_NEGATIVA,
                'Un lote no puede quedar con cantidad negativa.',
                ['campo' => 'cantidad_nueva']
            );
        }

        return DB::transaction(function () use ($loteId, $cantidadNueva, $motivo, $usuarioId) {
            $lote = Lote::query()->whereKey($loteId)->lockForUpdate()->first();

            if ($lote === null) {
                throw new ErrorDeDominio(
                    CodigoDeError::RECURSO_NO_ENCONTRADO,
                    'El lote indicado no existe.'
                );
            }

            $diferencia = bcsub($cantidadNueva, $lote->cantidad_actual, 3);

            if (bccomp($diferencia, '0', 3) === 0) {
                return $lote;
            }

            $anterior = $lote->cantidad_actual;
            $lote->cantidad_actual = $cantidadNueva;
            $lote->save();

            $this->registrarMovimiento(
                $lote, MovimientoInventario::TIPO_AJUSTE, $diferencia,
                $lote->costo_unitario, MovimientoInventario::ORIGEN_AJUSTE,
                $lote->id, $usuarioId, $motivo
            );

            $this->auditoria->registrar(
                entidad: 'Lote',
                entidadId: (int) $lote->id,
                accion: Auditoria::ACCION_ACTUALIZAR,
                anteriores: ['cantidad_actual' => $anterior],
                nuevos: ['cantidad_actual' => $cantidadNueva, 'motivo' => $motivo],
                usuarioId: $usuarioId,
            );

            return $lote->refresh();
        });
    }

    /**
     * Descuenta una cantidad tomando los lotes que vencen primero, partiendo
     * entre varios si hace falta (FEFO, RF-012).
     *
     * Si el disponible no alcanza, rechaza la venta entera **sin descontar
     * nada**: media venta descontada sería stock perdido sin documento que lo
     * explique. La comprobación ocurre con los lotes ya bloqueados — contar
     * antes y descontar después dejaría espacio a que otra operación consuma
     * lo que se acababa de contar.
     *
     * Devuelve el reparto: qué salió de cada lote y a qué costo, que es lo que
     * permite calcular después la utilidad real.
     *
     * @return array<int, array{lote_id: int, cantidad: string, costo_unitario: string}>
     */
    public function descontarPorVencimiento(
        int $productoId,
        string $cantidad,
        string $origenTipo,
        int $origenId,
        int $usuarioId,
        ?string $hoy = null,
    ): array {
        $producto = $this->productoDisponible($productoId);
        $hoy ??= now()->format('Y-m-d');

        return DB::transaction(function () use ($producto, $cantidad, $origenTipo, $origenId, $usuarioId, $hoy) {
            $lotes = $this->bloquearEnOrden($this->candidatosParaSalida((int) $producto->id, $hoy));

            $disponible = '0.000';
            foreach ($lotes as $lote) {
                $disponible = bcadd($disponible, $lote->cantidad_actual, 3);
            }

            if (bccomp($disponible, $cantidad, 3) < 0) {
                throw new ErrorDeDominio(
                    CodigoDeError::STOCK_INSUFICIENTE,
                    'No hay stock suficiente para cubrir la cantidad pedida.',
                    ['producto_id' => $producto->id, 'disponible' => $disponible, 'pedido' => $cantidad]
                );
            }

            $porRepartir = $cantidad;
            $reparto = [];

            foreach ($lotes as $lote) {
                if (bccomp($porRepartir, '0', 3) <= 0) {
                    break;
                }

                $deEsteLote = bccomp($lote->cantidad_actual, $porRepartir, 3) >= 0
                    ? $porRepartir
                    : $lote->cantidad_actual;

                $lote->cantidad_actual = bcsub($lote->cantidad_actual, $deEsteLote, 3);
                $lote->save();

                $this->registrarMovimiento(
                    $lote, MovimientoInventario::TIPO_SALIDA, '-'.$deEsteLote,
                    $lote->costo_unitario, $origenTipo, $origenId, $usuarioId
                );

                $reparto[] = [
                    'lote_id' => (int) $lote->id,
                    'cantidad' => $deEsteLote,
                    'costo_unitario' => $lote->costo_unitario,
                ];

                $porRepartir = bcsub($porRepartir, $deEsteLote, 3);
            }

            return $reparto;
        });
    }

    /**
     * Lotes que pueden cubrir una salida, en orden FEFO.
     *
     * Un lote vencido nunca se toma, aunque tenga existencia: venderlo sería
     * entregar mercadería vencida.
     *
     * @return array<int, int> ids en el orden en que deben bloquearse
     */
    private function candidatosParaSalida(int $productoId, string $hoy): array
    {
        return Lote::query()
            ->where('producto_id', $productoId)
            ->where('fecha_vencimiento', '>=', $hoy)
            ->where('cantidad_actual', '>', 0)
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Toma los lotes con bloqueo **siempre en el mismo orden**: vencimiento y,
     * a igualdad, id.
     *
     * El desempate por id no es cosmético. Dos lotes del mismo producto pueden
     * vencer el mismo día; sin un orden total y determinista, dos ventas
     * podrían tomarlos en sentidos opuestos y quedarse esperándose. Y vive
     * acá, en un solo lugar, para que cualquier operación que tome varias
     * filas use este orden y no el suyo — el día que otra lo invente, el
     * bloqueo mutuo vuelve.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Lote>
     */
    private function bloquearEnOrden(array $ids): array
    {
        $lotes = [];

        foreach ($ids as $id) {
            $lote = Lote::query()->whereKey($id)->lockForUpdate()->first();

            if ($lote !== null) {
                $lotes[] = $lote;
            }
        }

        return $lotes;
    }

    /** Stock disponible: suma de lotes con existencia que no estén vencidos. */
    public function stockDisponible(int $productoId, ?string $hoy = null): string
    {
        $hoy ??= now()->format('Y-m-d');

        $suma = Lote::query()
            ->where('producto_id', $productoId)
            ->where('fecha_vencimiento', '>=', $hoy)
            ->where('cantidad_actual', '>', 0)
            ->sum('cantidad_actual');

        return number_format((float) $suma, 3, '.', '');
    }

    /**
     * Lotes de un producto ordenados por vencimiento más próximo — el orden
     * en que la venta los va a consumir (FEFO, ADR-0004).
     *
     * @return Collection<int, Lote>
     */
    public function lotesDe(int $productoId): Collection
    {
        return Lote::query()
            ->where('producto_id', $productoId)
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get();
    }

    private function productoDisponible(int $productoId): Producto
    {
        $producto = Producto::query()->find($productoId);

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
                'No se puede mover stock de un producto desactivado.',
                ['campo' => 'producto_id']
            );
        }

        return $producto;
    }

    /**
     * El vencimiento se compara como fecha civil contra hoy, sin hora: es la
     * fecha impresa en el envase, no un instante.
     */
    private function garantizarVencimientoFuturo(string $fechaVencimiento): void
    {
        if ($fechaVencimiento <= now()->format('Y-m-d')) {
            throw new ErrorDeDominio(
                CodigoDeError::LOTE_VENCIMIENTO_PASADO,
                'No se puede ingresar mercadería vencida o que vence hoy.',
                ['campo' => 'fecha_vencimiento']
            );
        }
    }

    private function registrarMovimiento(
        Lote $lote,
        string $tipo,
        string $cantidad,
        string $costoUnitario,
        string $origenTipo,
        int $origenId,
        int $usuarioId,
        ?string $motivo = null,
    ): void {
        MovimientoInventario::query()->create([
            'lote_id' => $lote->id,
            'producto_id' => $lote->producto_id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'motivo' => $motivo,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'usuario_id' => $usuarioId,
            'created_at' => now(),
        ]);
    }
}
