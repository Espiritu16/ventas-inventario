<?php

namespace App\Dominios\Usuarios\Modelos;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Persona que opera el sistema. Su rol decide qué puede hacer; la matriz
 * completa vive en docs/requisitos/actores-permisos.md.
 */
class Usuario extends Authenticatable
{
    use HasFactory;

    public const ROL_ADMINISTRADOR = 'administrador';

    public const ROL_VENDEDOR = 'vendedor';

    /** Los dos únicos roles admitidos, iguales a los del CHECK de la tabla. */
    public const ROLES = [self::ROL_ADMINISTRADOR, self::ROL_VENDEDOR];

    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password', 'rol', 'activo'];

    /** Nunca sale en una serialización, ni siquiera por descuido (RNF-014). */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }
}
