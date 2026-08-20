<?php

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Dominios\Comprobantes\Servicios\SerieComprobanteService;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Ventas\Servicios\VentaService;
use Illuminate\Contracts\Console\Kernel;

/**
 * Un competidor: arranca su propio kernel, espera la barrera y ejecuta.
 *
 * Se ejecuta como proceso separado a propósito. Dos llamadas dentro del mismo
 * proceso comparten conexión y se ejecutan una tras otra: pasarían con bloqueo
 * y sin él, que es justamente el fallo que esto viene a detectar.
 *
 * Uso: competidor.php <base> <operacion> <instante-barrera> <args...>
 */
require __DIR__.'/../../../vendor/autoload.php';

// La base llega por argumento y se impone antes de arrancar: el proceso hijo
// no hereda la configuración de PHPUnit y, sin esto, operaría sobre la base de
// la aplicación mientras la prueba mira la del carril — competirían dos
// procesos sobre datos que nadie está observando.
putenv('DB_DATABASE='.$argv[1]);
$_ENV['DB_DATABASE'] = $argv[1];
$_SERVER['DB_DATABASE'] = $argv[1];

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$operacion, $barrera] = [$argv[2], (float) $argv[3]];

// Barrera: los dos kernels ya están arrancados y esperan el mismo instante.
// Sin ella el primero termina antes de que el segundo lea, y no compiten.
while (microtime(true) < $barrera) {
    usleep(200);
}

$inventario = new InventarioService(new AuditoriaService);
$respuesta = 'ok';

try {
    if ($operacion === 'ajustar') {
        $inventario->ajustar((int) $argv[4], $argv[5], 'merma', null, (int) $argv[6]);
    } elseif ($operacion === 'vender') {
        (new VentaService($inventario, new SerieComprobanteService))->registrar(
            DatosDeEntrada::desde([
                'cliente_id' => (int) $argv[4],
                'tipo_comprobante' => '03',
                'metodo_pago' => 'efectivo',
                'lineas' => [['producto_id' => (int) $argv[5], 'cantidad' => $argv[6], 'tipo_precio' => 'menor']],
            ]),
            Usuario::query()->findOrFail((int) $argv[7])
        );
    }
} catch (Throwable $error) {
    $respuesta = 'error: '.substr($error->getMessage(), 0, 80);
}

echo json_encode([
    'operacion' => $operacion,
    'respuesta' => $respuesta,
    'base' => config('database.connections.pgsql.database'),
]), "\n";
