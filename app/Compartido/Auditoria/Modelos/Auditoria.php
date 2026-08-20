<?php

namespace App\Compartido\Auditoria\Modelos;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora de operaciones sensibles (RNF-004).
 *
 * Es append-only por privilegios de la base: la aplicación solo tiene INSERT
 * y SELECT (MIG-009). Por eso el modelo no expone actualización ni borrado —
 * intentarlo fallaría en el motor, y conviene que también sea evidente acá.
 */
class Auditoria extends Model
{
    public const ACCION_CREAR = 'crear';

    public const ACCION_ACTUALIZAR = 'actualizar';

    public const ACCION_ELIMINAR = 'eliminar';

    protected $table = 'auditorias';

    public $timestamps = false;

    protected $fillable = [
        'entidad', 'entidad_id', 'accion', 'usuario_id',
        'origen', 'fecha', 'valores_anteriores', 'valores_nuevos',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
        ];
    }
}
