<?php

namespace App\Compartido\Auditoria;

use App\Compartido\Auditoria\Modelos\Auditoria;
use Illuminate\Support\Facades\Auth;

/**
 * Escribe la bitácora de operaciones sensibles (RNF-004).
 *
 * Recibe los valores ya acotados a los campos auditables de cada entidad, y
 * nunca serializa el objeto completo: así una contraseña, un dato del
 * certificado o cualquier campo que se agregue en el futuro no puede terminar
 * en la bitácora por descuido (RNF-014).
 *
 * S-08-B extiende la política a todas las operaciones sensibles; acá queda el
 * mecanismo y su primer uso, el cambio de precio.
 */
class AuditoriaService
{
    /**
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     */
    public function registrar(
        string $entidad,
        int $entidadId,
        string $accion,
        array $anteriores = [],
        array $nuevos = [],
        ?int $usuarioId = null,
        string $origen = 'usuario',
    ): Auditoria {
        return Auditoria::query()->create([
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'accion' => $accion,
            'usuario_id' => $usuarioId ?? Auth::id(),
            'origen' => $origen,
            'fecha' => now(),
            'valores_anteriores' => $anteriores === [] ? null : $anteriores,
            'valores_nuevos' => $nuevos === [] ? null : $nuevos,
        ]);
    }
}
