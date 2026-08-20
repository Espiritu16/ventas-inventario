<?php

namespace App\Compartido\Idempotencia;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Ventas\Modelos\Venta;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Evita que un reintento registre dos veces la misma operación (RF-011).
 *
 * El caso no es hipotético: un doble clic o una recarga en la caja producen
 * dos peticiones idénticas, y una venta duplicada descuenta stock real y
 * consume un correlativo tributario.
 *
 * La huella acompaña a la clave porque son dos problemas distintos: la misma
 * clave con los mismos datos es un reintento y se responde con la venta
 * original; la misma clave con datos distintos es un error de quien la envía y
 * se rechaza, en vez de devolverle una venta que no es la que pidió.
 */
class RegistroDeOperaciones
{
    private const HORAS_DE_RETENCION = 24;

    /**
     * Ejecuta la operación una sola vez por clave.
     *
     * @param  array<string, mixed>  $peticion
     * @param  callable(): Venta  $operacion
     */
    public function unaSolaVez(string $clave, array $peticion, callable $operacion): Venta
    {
        $this->garantizarFormato($clave);
        $huella = $this->huellaDe($peticion);

        $previa = OperacionIdempotente::query()->where('clave', $clave)->first();

        if ($previa !== null) {
            return $this->resolverPrevia($previa, $huella);
        }

        // La inserción va primero y fuera de la transacción de la venta: es lo
        // que hace que dos peticiones simultáneas con la misma clave compitan
        // por esta fila en vez de por el stock.
        try {
            $registro = OperacionIdempotente::query()->create([
                'clave' => $clave,
                'huella' => $huella,
                'expira_en' => now()->addHours(self::HORAS_DE_RETENCION),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Otra petición ganó la carrera mientras tanto.
            $previa = OperacionIdempotente::query()->where('clave', $clave)->firstOrFail();

            return $this->resolverPrevia($previa, $huella);
        }

        try {
            $venta = $operacion();
        } catch (\Throwable $error) {
            // Si la venta falla, la clave se libera: fue un intento fallido, no
            // una operación ya hecha, y quien reintente debe poder hacerlo.
            $registro->delete();

            throw $error;
        }

        $registro->venta_id = $venta->id;
        $registro->save();

        return $venta;
    }

    private function resolverPrevia(OperacionIdempotente $previa, string $huella): Venta
    {
        if ($previa->huella !== $huella) {
            throw new ErrorDeDominio(
                CodigoDeError::OPERACION_DUPLICADA,
                'Esa clave de operación ya se usó con datos distintos.',
                ['campo' => 'Idempotency-Key']
            );
        }

        if ($previa->estaExpirada()) {
            throw new ErrorDeDominio(
                CodigoDeError::OPERACION_DUPLICADA,
                'Esa clave de operación expiró; abre la venta de nuevo.',
                ['campo' => 'Idempotency-Key']
            );
        }

        if ($previa->venta_id === null) {
            // Hay otra petición con la misma clave en curso ahora mismo.
            throw new ErrorDeDominio(
                CodigoDeError::OPERACION_DUPLICADA,
                'Esa venta se está registrando en este momento.',
                ['campo' => 'Idempotency-Key']
            );
        }

        return $previa->venta()->firstOrFail();
    }

    private function garantizarFormato(string $clave): void
    {
        $uuidV4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

        if (preg_match($uuidV4, $clave) !== 1) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FORMATO_INVALIDO,
                'La clave de operación debe ser un UUID versión 4.',
                ['campo' => 'Idempotency-Key']
            );
        }
    }

    /**
     * Huella estable de la petición: las mismas líneas en distinto orden son
     * la misma venta, así que se ordenan antes de resumir.
     *
     * @param  array<string, mixed>  $peticion
     */
    private function huellaDe(array $peticion): string
    {
        $lineas = $peticion['lineas'] ?? [];

        if (is_array($lineas)) {
            usort($lineas, fn ($a, $b) => json_encode($a) <=> json_encode($b));
            $peticion['lineas'] = $lineas;
        }

        ksort($peticion);

        return hash('sha256', (string) json_encode($peticion));
    }
}
