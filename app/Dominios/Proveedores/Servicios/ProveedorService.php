<?php

namespace App\Dominios\Proveedores\Servicios;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Documentos\ValidadorDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Proveedores\Modelos\Proveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Proveedores (RF-005, contrato docs/contratos/proveedores.md v1).
 */
class ProveedorService
{
    private const POR_PAGINA = 20;

    /** Dígitos, espacios, `+` y guiones; sin letras. */
    private const FORMATO_TELEFONO = '/^[0-9+\- ]{6,20}$/';

    public function crear(DatosDeEntrada $datos): Proveedor
    {
        $campos = ValidadorDeDominio::validar(
            $this->normalizar($datos->todos()),
            [
                'razon_social' => ['required', 'string', 'between:3,200'],
                'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
                'telefono' => ['sometimes', 'nullable', 'string', 'regex:'.self::FORMATO_TELEFONO],
                'email' => ['sometimes', 'nullable', 'string', 'email', 'max:150'],
            ]
        );

        // El tipo no se recibe: un proveedor siempre es RUC (RF-005).
        $campos['tipo_documento'] = TipoDeDocumento::RUC->value;
        $campos['numero_documento'] = ValidadorDeDocumento::numeroValidado(
            TipoDeDocumento::RUC,
            $datos->valor('numero_documento')
        );

        $this->garantizarDocumentoLibre((string) $campos['numero_documento']);

        return Proveedor::query()->create($campos)->refresh();
    }

    public function actualizar(int $id, DatosDeEntrada $datos): Proveedor
    {
        $proveedor = $this->encontrar($id);

        // El documento identifica al proveedor en compras ya registradas:
        // cambiarlo dejaría esos comprobantes apuntando a otra empresa.
        if ($datos->fueEnviado('numero_documento')
            && (string) $datos->valor('numero_documento') !== $proveedor->numero_documento) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FORMATO_INVALIDO,
                'El número de documento de un proveedor no se puede cambiar.',
                ['campo' => 'numero_documento']
            );
        }

        $enviados = array_intersect_key(
            $datos->todos(),
            array_flip(['razon_social', 'direccion', 'telefono', 'email', 'activo'])
        );

        $campos = ValidadorDeDominio::validar(
            $this->normalizar($enviados),
            [
                'razon_social' => ['sometimes', 'required', 'string', 'between:3,200'],
                'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
                'telefono' => ['sometimes', 'nullable', 'string', 'regex:'.self::FORMATO_TELEFONO],
                'email' => ['sometimes', 'nullable', 'string', 'email', 'max:150'],
                'activo' => ['sometimes', 'required', 'boolean'],
            ]
        );

        if (array_key_exists('activo', $campos)) {
            $campos['activo'] = filter_var($campos['activo'], FILTER_VALIDATE_BOOLEAN);
        }

        $proveedor->fill($campos)->save();

        return $proveedor->refresh();
    }

    /** @return LengthAwarePaginator<int, Proveedor> */
    public function listar(?string $buscar = null, bool $soloActivos = true, int $pagina = 1): LengthAwarePaginator
    {
        return Proveedor::query()
            ->when($soloActivos, fn ($consulta) => $consulta->where('activo', true))
            ->when($buscar !== null && trim($buscar) !== '', function ($consulta) use ($buscar) {
                $texto = '%'.trim($buscar).'%';

                $consulta->where(function ($agrupada) use ($texto) {
                    $agrupada->where('razon_social', 'ilike', $texto)
                        ->orWhere('numero_documento', 'like', $texto);
                });
            })
            ->orderBy('razon_social')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    public function encontrar(int $id): Proveedor
    {
        $proveedor = Proveedor::query()->find($id);

        if ($proveedor === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El proveedor indicado no existe.'
            );
        }

        return $proveedor;
    }

    private function garantizarDocumentoLibre(string $numero): void
    {
        $existe = Proveedor::query()
            ->where('tipo_documento', TipoDeDocumento::RUC->value)
            ->where('numero_documento', $numero)
            ->exists();

        if ($existe) {
            throw new ErrorDeDominio(
                CodigoDeError::DOCUMENTO_DUPLICADO,
                'Ya existe un proveedor con ese RUC.',
                ['campo' => 'numero_documento']
            );
        }
    }

    /**
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function normalizar(array $campos): array
    {
        if (isset($campos['razon_social']) && is_string($campos['razon_social'])) {
            $campos['razon_social'] = (string) preg_replace('/\s+/u', ' ', trim($campos['razon_social']));
        }

        foreach (['direccion', 'telefono'] as $campo) {
            if (isset($campos[$campo]) && is_string($campos[$campo])) {
                $campos[$campo] = trim($campos[$campo]);
            }
        }

        if (isset($campos['email']) && is_string($campos['email'])) {
            $campos['email'] = mb_strtolower(trim($campos['email']));
        }

        return $campos;
    }
}
