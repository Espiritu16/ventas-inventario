<?php

namespace App\Compartido\Idempotencia;

use App\Dominios\Ventas\Modelos\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperacionIdempotente extends Model
{
    protected $table = 'operaciones_idempotentes';

    protected $fillable = ['clave', 'huella', 'venta_id', 'expira_en'];

    protected function casts(): array
    {
        return ['expira_en' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function estaExpirada(): bool
    {
        return $this->expira_en->isPast();
    }
}
