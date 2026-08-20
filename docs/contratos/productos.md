# Contrato — Catálogo (categorías y productos)

Autoridad: Arquitectura. Versión del contrato: v1.

## GET /categorias
- Ruta real: GET /categorias
- Query params: `incluirInactivas?: boolean (opcional, default false)`
- Response éxito: listado de categorías; colección acotada, sin paginación
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` (completo) y `vendedor` (solo lectura)

## POST /categorias
- Request: `{ nombre: string (requerido), descripcion?: string (opcional) }`
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, DOCUMENTO_DUPLICADO
- Autenticación: requerida, rol `administrador`
- Idempotencia: deduplicación mediante la unicidad de `nombre`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| nombre (body) | string | requerido, no vacío ni solo espacios | texto libre | 2 a 80 caracteres | recorte de extremos | único, comparado sin distinguir mayúsculas | CAMPO_REQUERIDO / DOCUMENTO_DUPLICADO | RF-003 |
| descripcion (body) | string | opcional, default null | texto libre | máximo 255 caracteres | recorte de extremos | no aplica | CAMPO_FUERA_DE_RANGO | derivado del schema |

## PATCH /categorias/{id}
- Request: subconjunto de `{ nombre, descripcion, activo }`
- Regla de negocio: desactivar una categoría no afecta a los productos ya asociados (RF-003); una categoría inactiva no se ofrece al crear productos nuevos.
- Errores: RECURSO_NO_ENCONTRADO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## GET /productos
- Ruta real: GET /productos
- Query params: `buscar?: string (opcional — coincide contra código y nombre)`, `categoriaId?: entero (opcional)`, `soloActivos?: boolean (opcional, default true)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por nombre y desempatado por `id`. **Para el rol `vendedor` no incluye costo ni margen** (proyección acotada, según la matriz de permisos)
- **Campo `stockDisponible`: ausente hasta S-04-B, presente desde entonces.** Se calcula sobre los lotes, que crea la compra en S-04-B; hasta ese sprint no existe la tabla de la que derivarlo. **El campo se omite, no se devuelve en cero.** Un cero que significa "todavía no se sabe" es indistinguible de uno que significa "no hay existencias", y quien consuma este contrato —S-02-F construye la pantalla de catálogo antes de que exista el stock— no tendría forma de diferenciarlos y podría mostrar "sin stock" sobre un producto que nunca se compró. La ausencia del campo es explícita y verificable; un cero falso no lo es. Enmienda de Arquitectura del 2026-08-19, a partir de la contradicción que reportó `implementation-backend` al implementar S-02-B: el contrato describía el estado final del recurso y el alcance de S-02-B excluye las existencias, así que ambos documentos aprobados no podían cumplirse a la vez
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`
- Soporte de índices: `buscar` se apoya en el índice de `nombre` y en el único de `codigo`; `categoriaId` en el índice de `categoria_id` (ver docs/persistencia/modelo.md)

## POST /productos
- Request: `{ codigo: string (requerido), nombre: string (requerido), categoriaId: entero (requerido), unidadMedida: string (requerido), precioMenor: decimal (requerido), precioMayor: decimal (requerido), stockMinimo?: decimal (opcional, default 0) }`
- Idempotencia: deduplicación mediante la unicidad de `codigo`
- Errores: CAMPO_REQUERIDO, CAMPO_FORMATO_INVALIDO, CAMPO_FUERA_DE_RANGO, PRODUCTO_CODIGO_DUPLICADO, PRODUCTO_PRECIO_MAYOR_INVALIDO, RECURSO_NO_ENCONTRADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| codigo (body) | string | requerido, no vacío | letras, dígitos, guion y guion bajo; sin espacios | 1 a 40 caracteres | recorte de extremos y mayúsculas | único entre todos los productos, activos e inactivos | **`CAMPO_FORMATO_INVALIDO` si el formato o la longitud fallan; `PRODUCTO_CODIGO_DUPLICADO` solo si ya existe** | RF-004 |

> **Enmienda de Arquitectura, 2026-08-19.** La tabla asignaba `PRODUCTO_CODIGO_DUPLICADO`
> como único código de error del campo, así que un código con espacios respondía
> "duplicado". El servicio hacía lo que el contrato decía; el contrato estaba mal. La
> pantalla de S-02-F le habría dicho "código duplicado" a alguien que escribió un
> espacio, y esa persona habría cambiado el código en vez de borrar el espacio. Lo notó
> `qa` al validar S-02-B y fue a comprobar si era divergencia antes de reportarlo:
> no lo era.
| nombre (body) | string | requerido, no vacío ni solo espacios | texto libre | 3 a 150 caracteres | recorte, espacios internos colapsados | no aplica | CAMPO_REQUERIDO | RF-004 |
| categoriaId (body) | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir y estar activa | RECURSO_NO_ENCONTRADO | RF-004 |
| unidadMedida (body) | enum | requerido, default `NIU` | código de unidad del catálogo de SUNAT | 2 a 10 caracteres | mayúsculas | debe existir en el catálogo vigente de SUNAT, porque viaja en el comprobante | CAMPO_FORMATO_INVALIDO | RF-004 más docs/integraciones/sunat.md |
| precioMenor (body) | decimal | requerido | hasta 4 decimales | mayor que 0, máximo 99 999 999,9999 | se rechaza notación con separador de miles; no se corrige en silencio | no aplica | CAMPO_FUERA_DE_RANGO | RF-004 |
| precioMayor (body) | decimal | requerido | hasta 4 decimales | mayor que 0 | igual que el anterior | **debe ser menor o igual que `precioMenor`** | PRODUCTO_PRECIO_MAYOR_INVALIDO | RF-004: regla aprobada por el usuario |
| stockMinimo (body) | decimal | opcional, default 0 | hasta 3 decimales | mayor o igual que 0 | ninguna | no aplica | CAMPO_FUERA_DE_RANGO | RF-019 |

## PATCH /productos/{id}
- Request: subconjunto de `{ nombre, categoriaId, unidadMedida, precioMenor, precioMayor, stockMinimo, activo }`. **`codigo` no se puede cambiar** una vez creado, porque ya viajó en comprobantes emitidos.
- Efectos: todo cambio de precio se registra en `Auditoria` con su valor anterior (RNF-004)
- Errores: RECURSO_NO_ENCONTRADO, PRODUCTO_PRECIO_MAYOR_INVALIDO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`
- Regla de negocio: desactivar un producto no borra su stock ni su historial; solo impide comprarlo y venderlo (RF-004).

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
