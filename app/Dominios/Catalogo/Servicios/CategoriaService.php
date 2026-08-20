<?php

namespace App\Dominios\Catalogo\Servicios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Modelos\Categoria;
use Illuminate\Database\Eloquent\Collection;

/**
 * Categorías del catálogo (RF-003, contrato docs/contratos/productos.md v1).
 */
class CategoriaService
{
    public function crear(DatosDeCatalogo $datos): Categoria
    {
        $campos = ValidadorDeDominio::validar(
            $this->normalizar($datos->todos()),
            [
                'nombre' => ['required', 'string', 'between:2,80'],
                'descripcion' => ['nullable', 'string', 'max:255'],
            ]
        );

        $this->garantizarNombreUnico($campos['nombre']);

        // Se relee para devolver los valores por defecto de la base —hoy
        // `activo`— y no un null que la fila no tiene.
        return Categoria::query()->create($campos)->refresh();
    }

    public function actualizar(int $id, DatosDeCatalogo $datos): Categoria
    {
        $categoria = $this->encontrar($id);

        $enviados = array_intersect_key($datos->todos(), array_flip(['nombre', 'descripcion', 'activo']));

        $campos = ValidadorDeDominio::validar(
            $this->normalizar($enviados),
            [
                'nombre' => ['sometimes', 'required', 'string', 'between:2,80'],
                'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
                'activo' => ['sometimes', 'required', 'boolean'],
            ]
        );

        if (isset($campos['nombre'])) {
            $this->garantizarNombreUnico($campos['nombre'], $id);
        }

        if (array_key_exists('activo', $campos)) {
            $campos['activo'] = filter_var($campos['activo'], FILTER_VALIDATE_BOOLEAN);
        }

        $categoria->fill($campos)->save();

        return $categoria->refresh();
    }

    /** @return Collection<int, Categoria> */
    public function listar(bool $incluirInactivas = false): Collection
    {
        return Categoria::query()
            ->when(! $incluirInactivas, fn ($consulta) => $consulta->where('activo', true))
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    public function encontrar(int $id): Categoria
    {
        $categoria = Categoria::query()->find($id);

        if ($categoria === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'La categoría indicada no existe.'
            );
        }

        return $categoria;
    }

    /**
     * La unicidad del nombre no distingue mayúsculas: "Abarrotes" y
     * "ABARROTES" son la misma categoría para quien la usa, y permitir ambas
     * dejaría el catálogo con duplicados que solo se ven al leerlos.
     *
     * Se comprueba aparte de la validación de formato para que el rechazo
     * lleve su propio código: un nombre repetido y uno demasiado corto son
     * problemas distintos y quien los recibe necesita distinguirlos.
     *
     * El código es propio de la categoría y no `DOCUMENTO_DUPLICADO`, que la
     * taxonomía reserva para el documento de un cliente o proveedor: una
     * categoría no tiene documento, y el mismo código significando dos cosas
     * obligaría a la pantalla a saber en qué servicio está para traducirlo al
     * campo correcto. Y nombra su campo, porque el mensaje se muestra al lado
     * de él.
     */
    private function garantizarNombreUnico(string $nombre, ?int $exceptoId = null): void
    {
        $existe = Categoria::query()
            ->whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])
            ->when($exceptoId !== null, fn ($consulta) => $consulta->whereKeyNot($exceptoId))
            ->exists();

        if ($existe) {
            throw new ErrorDeDominio(
                CodigoDeError::CATEGORIA_NOMBRE_DUPLICADO,
                'Ya existe una categoría con ese nombre.',
                ['campo' => 'nombre']
            );
        }
    }

    /**
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function normalizar(array $campos): array
    {
        foreach (['nombre', 'descripcion'] as $campo) {
            if (isset($campos[$campo]) && is_string($campos[$campo])) {
                $campos[$campo] = trim($campos[$campo]);
            }
        }

        return $campos;
    }
}
