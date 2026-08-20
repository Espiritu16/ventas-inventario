<?php

namespace App\Dominios\Clientes\Servicios;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Documentos\ValidadorDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Clientes\Modelos\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Clientes (RF-010, contrato docs/contratos/clientes.md v1).
 */
class ClienteService
{
    private const POR_PAGINA = 20;

    private const FORMATO_TELEFONO = '/^[0-9+\- ]{6,20}$/';

    public function crear(DatosDeEntrada $datos): Cliente
    {
        $tipo = ValidadorDeDocumento::tipoDesde($datos->valor('tipo_documento'));

        $campos = ValidadorDeDominio::validar(
            $this->normalizar($datos->todos()),
            [
                'nombre' => ['required', 'string', 'between:3,200'],
                'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
                'telefono' => ['sometimes', 'nullable', 'string', 'regex:'.self::FORMATO_TELEFONO],
                'email' => ['sometimes', 'nullable', 'string', 'email', 'max:150'],
            ]
        );

        $campos['tipo_documento'] = $tipo->value;
        $campos['numero_documento'] = ValidadorDeDocumento::numeroValidado($tipo, $datos->valor('numero_documento'));

        if ($campos['numero_documento'] !== null) {
            $this->garantizarDocumentoLibre($tipo, $campos['numero_documento']);
        }

        return Cliente::query()->create($campos)->refresh();
    }

    public function actualizar(int $id, DatosDeEntrada $datos): Cliente
    {
        $cliente = $this->encontrar($id);

        // Tipo y número identifican al cliente en comprobantes ya emitidos.
        foreach (['tipo_documento', 'numero_documento'] as $campo) {
            $enviado = $datos->fueEnviado($campo) ? $datos->valor($campo) : null;

            if ($datos->fueEnviado($campo) && (string) $enviado !== (string) $cliente->{$campo}) {
                throw new ErrorDeDominio(
                    CodigoDeError::CAMPO_FORMATO_INVALIDO,
                    'El documento de un cliente no se puede cambiar.',
                    ['campo' => $campo]
                );
            }
        }

        $enviados = array_intersect_key(
            $datos->todos(),
            array_flip(['nombre', 'direccion', 'telefono', 'email', 'activo'])
        );

        $campos = ValidadorDeDominio::validar(
            $this->normalizar($enviados),
            [
                'nombre' => ['sometimes', 'required', 'string', 'between:3,200'],
                'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
                'telefono' => ['sometimes', 'nullable', 'string', 'regex:'.self::FORMATO_TELEFONO],
                'email' => ['sometimes', 'nullable', 'string', 'email', 'max:150'],
                'activo' => ['sometimes', 'required', 'boolean'],
            ]
        );

        if (array_key_exists('activo', $campos)) {
            $campos['activo'] = filter_var($campos['activo'], FILTER_VALIDATE_BOOLEAN);
        }

        $cliente->fill($campos)->save();

        return $cliente->refresh();
    }

    /** @return LengthAwarePaginator<int, Cliente> */
    public function listar(?string $buscar = null, int $pagina = 1): LengthAwarePaginator
    {
        return Cliente::query()
            ->when($buscar !== null && trim($buscar) !== '', function ($consulta) use ($buscar) {
                $texto = '%'.trim($buscar).'%';

                $consulta->where(function ($agrupada) use ($texto) {
                    $agrupada->where('nombre', 'ilike', $texto)
                        ->orWhere('numero_documento', 'like', $texto);
                });
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    public function encontrar(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);

        if ($cliente === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El cliente indicado no existe.'
            );
        }

        return $cliente;
    }

    private function garantizarDocumentoLibre(TipoDeDocumento $tipo, string $numero): void
    {
        $existe = Cliente::query()
            ->where('tipo_documento', $tipo->value)
            ->where('numero_documento', $numero)
            ->exists();

        if ($existe) {
            throw new ErrorDeDominio(
                CodigoDeError::DOCUMENTO_DUPLICADO,
                "Ya existe un cliente con ese {$tipo->descripcion()}.",
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
        if (isset($campos['nombre']) && is_string($campos['nombre'])) {
            $campos['nombre'] = (string) preg_replace('/\s+/u', ' ', trim($campos['nombre']));
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
