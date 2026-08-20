<?php

namespace App\Dominios\Catalogo\Servicios;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Auditoria\Modelos\Auditoria;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Productos del catálogo (RF-004, contrato docs/contratos/productos.md v1).
 */
class ProductoService
{
    private const POR_PAGINA = 20;

    /**
     * Campos cuyo cambio deja rastro en la bitácora (RNF-004).
     *
     * Es una lista explícita, no "todo lo que cambió": así un campo nuevo no
     * empieza a auditarse solo, y ningún dato sensible puede colarse por
     * haberse agregado al modelo.
     */
    private const CAMPOS_AUDITABLES = ['precio_menor', 'precio_mayor'];

    /** Hasta 4 decimales, sin separador de miles ni notación científica. */
    private const FORMATO_PRECIO = '/^\d{1,8}(\.\d{1,4})?$/';

    /** Hasta 3 decimales, igual de estricto. */
    private const FORMATO_CANTIDAD = '/^\d{1,9}(\.\d{1,3})?$/';

    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function crear(DatosDeCatalogo $datos): Producto
    {
        $campos = ValidadorDeDominio::validar(
            $this->normalizar($datos->todos()),
            [
                'codigo' => ['required', 'string', 'between:1,40', 'regex:/^[A-Z0-9_-]+$/'],
                'nombre' => ['required', 'string', 'between:3,150'],
                'categoria_id' => ['required', 'integer', 'min:1'],
                'unidad_medida' => ['required', 'string', 'between:2,10'],
                'precio_menor' => ['required', 'regex:'.self::FORMATO_PRECIO],
                'precio_mayor' => ['required', 'regex:'.self::FORMATO_PRECIO],
                'stock_minimo' => ['sometimes', 'required', 'regex:'.self::FORMATO_CANTIDAD],
            ],
            ['codigo' => CodigoDeError::PRODUCTO_CODIGO_DUPLICADO]
        );

        $this->garantizarUnidadValida($campos['unidad_medida']);
        $this->garantizarCodigoLibre($campos['codigo']);
        $this->garantizarCategoriaDisponible((int) $campos['categoria_id']);
        $this->garantizarPreciosCoherentes($campos['precio_menor'], $campos['precio_mayor']);

        // Se relee para que los valores por defecto de la base —hoy el stock
        // mínimo— lleguen a quien lo creó, y no un null que la fila no tiene.
        return Producto::query()->create($campos)->refresh();
    }

    public function actualizar(int $id, DatosDeCatalogo $datos, ?Usuario $actor = null): Producto
    {
        $producto = $this->encontrar($id);

        // El código ya viajó en comprobantes emitidos: cambiarlo dejaría esos
        // documentos apuntando a un artículo que ya no se llama así.
        if ($datos->fueEnviado('codigo') && $datos->valor('codigo') !== $producto->codigo) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FORMATO_INVALIDO,
                'El código de un producto no se puede cambiar.',
                ['campo' => 'codigo']
            );
        }

        $enviados = array_intersect_key($datos->todos(), array_flip([
            'nombre', 'categoria_id', 'unidad_medida', 'precio_menor',
            'precio_mayor', 'stock_minimo', 'activo',
        ]));

        $campos = ValidadorDeDominio::validar(
            $this->normalizar($enviados),
            [
                'nombre' => ['sometimes', 'required', 'string', 'between:3,150'],
                'categoria_id' => ['sometimes', 'required', 'integer', 'min:1'],
                'unidad_medida' => ['sometimes', 'required', 'string', 'between:2,10'],
                'precio_menor' => ['sometimes', 'required', 'regex:'.self::FORMATO_PRECIO],
                'precio_mayor' => ['sometimes', 'required', 'regex:'.self::FORMATO_PRECIO],
                'stock_minimo' => ['sometimes', 'required', 'regex:'.self::FORMATO_CANTIDAD],
                'activo' => ['sometimes', 'required', 'boolean'],
            ]
        );

        if (isset($campos['unidad_medida'])) {
            $this->garantizarUnidadValida($campos['unidad_medida']);
        }

        if (isset($campos['categoria_id'])) {
            $this->garantizarCategoriaExiste((int) $campos['categoria_id']);
        }

        if (array_key_exists('activo', $campos)) {
            $campos['activo'] = filter_var($campos['activo'], FILTER_VALIDATE_BOOLEAN);
        }

        // Los precios se comparan ya combinados con los actuales: cambiar solo
        // uno de los dos también puede romper la regla.
        $this->garantizarPreciosCoherentes(
            $campos['precio_menor'] ?? $producto->precio_menor,
            $campos['precio_mayor'] ?? $producto->precio_mayor,
        );

        $anteriores = $this->soloAuditables($producto->getAttributes());

        $producto->fill($campos)->save();
        $producto->refresh();

        $this->registrarCambioDePrecio($producto, $anteriores, $actor);

        return $producto;
    }

    /** @return LengthAwarePaginator<int, Producto> */
    public function listar(
        ?string $buscar = null,
        ?int $categoriaId = null,
        bool $soloActivos = true,
        int $pagina = 1,
    ): LengthAwarePaginator {
        return Producto::query()
            ->when($soloActivos, fn ($consulta) => $consulta->where('activo', true))
            ->when($categoriaId !== null, fn ($consulta) => $consulta->where('categoria_id', $categoriaId))
            ->when($buscar !== null && trim($buscar) !== '', function ($consulta) use ($buscar) {
                $texto = '%'.trim($buscar).'%';

                $consulta->where(function ($agrupada) use ($texto) {
                    $agrupada->where('codigo', 'ilike', $texto)
                        ->orWhere('nombre', 'ilike', $texto);
                });
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    public function encontrar(int $id): Producto
    {
        $producto = Producto::query()->find($id);

        if ($producto === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El producto indicado no existe.'
            );
        }

        return $producto;
    }

    private function garantizarUnidadValida(string $unidad): void
    {
        /** @var array<int, string> $catalogo */
        $catalogo = config('sunat.unidades_de_medida', []);

        if (! in_array($unidad, $catalogo, true)) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FORMATO_INVALIDO,
                'La unidad de medida no pertenece al catálogo de SUNAT.',
                ['campo' => 'unidad_medida']
            );
        }
    }

    private function garantizarCodigoLibre(string $codigo): void
    {
        if (Producto::query()->where('codigo', $codigo)->exists()) {
            throw new ErrorDeDominio(
                CodigoDeError::PRODUCTO_CODIGO_DUPLICADO,
                'Ya existe un producto con ese código.',
                ['campo' => 'codigo']
            );
        }
    }

    /** Al crear, la categoría además debe estar activa (RF-003). */
    private function garantizarCategoriaDisponible(int $categoriaId): void
    {
        $categoria = Categoria::query()->find($categoriaId);

        if ($categoria === null || ! $categoria->activo) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'La categoría indicada no existe o está desactivada.',
                ['campo' => 'categoria_id']
            );
        }
    }

    private function garantizarCategoriaExiste(int $categoriaId): void
    {
        if (! Categoria::query()->whereKey($categoriaId)->exists()) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'La categoría indicada no existe.',
                ['campo' => 'categoria_id']
            );
        }
    }

    /**
     * La comparación es decimal, no de punto flotante: con float, "0.1 + 0.2"
     * ya no es "0.3", y acá se decide si un precio es válido.
     */
    private function garantizarPreciosCoherentes(string $menor, string $mayor): void
    {
        if (bccomp($menor, '0', 4) <= 0 || bccomp($mayor, '0', 4) <= 0) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FUERA_DE_RANGO,
                'Los precios deben ser mayores que cero.',
                ['campo' => 'precio_menor']
            );
        }

        if (bccomp($mayor, $menor, 4) > 0) {
            throw new ErrorDeDominio(
                CodigoDeError::PRODUCTO_PRECIO_MAYOR_INVALIDO,
                'El precio al por mayor no puede superar al precio al por menor.',
                ['campo' => 'precio_mayor']
            );
        }
    }

    /**
     * @param  array<string, mixed>  $anteriores
     */
    private function registrarCambioDePrecio(Producto $producto, array $anteriores, ?Usuario $actor): void
    {
        $nuevos = $this->soloAuditables($producto->getAttributes());

        $cambiados = array_filter(
            $nuevos,
            fn (mixed $valor, string $campo) => bccomp((string) $valor, (string) $anteriores[$campo], 4) !== 0,
            ARRAY_FILTER_USE_BOTH
        );

        if ($cambiados === []) {
            return;
        }

        $this->auditoria->registrar(
            entidad: 'Producto',
            entidadId: (int) $producto->id,
            accion: Auditoria::ACCION_ACTUALIZAR,
            anteriores: array_intersect_key($anteriores, $cambiados),
            nuevos: $cambiados,
            usuarioId: $actor?->id,
        );
    }

    /**
     * @param  array<string, mixed>  $atributos
     * @return array<string, mixed>
     */
    private function soloAuditables(array $atributos): array
    {
        return array_intersect_key($atributos, array_flip(self::CAMPOS_AUDITABLES));
    }

    /**
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function normalizar(array $campos): array
    {
        if (isset($campos['codigo']) && is_string($campos['codigo'])) {
            $campos['codigo'] = mb_strtoupper(trim($campos['codigo']));
        }

        if (isset($campos['nombre']) && is_string($campos['nombre'])) {
            $campos['nombre'] = (string) preg_replace('/\s+/u', ' ', trim($campos['nombre']));
        }

        if (isset($campos['unidad_medida']) && is_string($campos['unidad_medida'])) {
            $campos['unidad_medida'] = mb_strtoupper(trim($campos['unidad_medida']));
        }

        // Los importes se manejan como cadena de punta a punta: convertirlos a
        // float para validarlos perdería exactitud antes de guardarlos.
        foreach (['precio_menor', 'precio_mayor', 'stock_minimo'] as $campo) {
            if (isset($campos[$campo]) && (is_int($campos[$campo]) || is_float($campos[$campo]))) {
                $campos[$campo] = (string) $campos[$campo];
            }
        }

        return $campos;
    }
}
