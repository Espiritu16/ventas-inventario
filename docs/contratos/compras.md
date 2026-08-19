# Contrato — Compras e ingreso de mercadería

Autoridad: Arquitectura. Versión del contrato: v1.

Esta es la única operación que **crea existencias**. Todo lote nace acá.

## GET /compras
- Query params: `desde?: fecha (opcional)`, `hasta?: fecha (opcional)`, `proveedorId?: entero (opcional)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por fecha de emisión descendente y desempatado por `id`
- Errores: NO_AUTENTICADO, NO_AUTORIZADO, CAMPO_FORMATO_INVALIDO
- Autenticación: requerida, rol `administrador`
- Soporte de índices: `desde`/`hasta` sobre el índice de `fecha_emision`; `proveedorId` sobre la unicidad compuesta que ya encabeza `proveedor_id`

## GET /compras/{id}
- Response éxito: la compra con sus líneas, y por cada línea el lote que generó
- Errores: RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## POST /compras
- Ruta real: POST /compras
- Request:
  ```
  {
    proveedorId: entero (requerido),
    tipoDocumento: string (requerido),
    serieDocumento: string (requerido),
    numeroDocumento: string (requerido),
    fechaEmision: fecha (requerido),
    lineas: [ { productoId: entero (requerido), cantidad: decimal (requerido),
                costoUnitario: decimal (requerido), codigoLote: string (requerido),
                fechaVencimiento: fecha (requerido) } ] (requerido, al menos una)
  }
  ```
- Response éxito: compra registrada, con el stock ya incrementado y los movimientos de kardex generados
- Idempotencia: **deduplicación** mediante la unicidad de `(proveedor, tipo, serie, número)` del documento. Registrar dos veces la misma factura de compra duplicaría stock, y eso es inaceptable para el negocio
- Concurrencia: no aplica — una compra se registra una sola vez y no compite con otra escritura sobre la misma fila
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, COMPRA_SIN_LINEAS, COMPRA_DOCUMENTO_DUPLICADO, LOTE_VENCIMIENTO_PASADO, PRODUCTO_INACTIVO, RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| proveedorId (body) | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir y estar activo | RECURSO_NO_ENCONTRADO | RF-006 |
| tipoDocumento (body) | enum | requerido | uno de `01` (factura), `03` (boleta), `09` (guía), `NA` (sin comprobante) | no aplica | mayúsculas | no aplica | CAMPO_FORMATO_INVALIDO | RF-006; catálogo de SUNAT |
| serieDocumento (body) | string | requerido, no vacío | alfanumérico | 1 a 4 caracteres | recorte y mayúsculas | no aplica | CAMPO_REQUERIDO | RF-006 |
| numeroDocumento (body) | string | requerido, no vacío | solo dígitos | 1 a 8 caracteres | recorte; se conservan los ceros a la izquierda tal como llegan | la combinación con proveedor, tipo y serie debe ser única | COMPRA_DOCUMENTO_DUPLICADO | RF-006 |
| fechaEmision (body) | fecha de calendario | requerido | `AAAA-MM-DD` | no puede ser futura; no más de 365 días atrás | ninguna — es fecha local, no instante | no aplica | CAMPO_FUERA_DE_RANGO | RF-006; ventana de 365 días propuesta |
| lineas (body) | colección | requerido | no aplica | de 1 a 200 elementos | ninguna | una compra sin líneas se rechaza | COMPRA_SIN_LINEAS / CAMPO_FUERA_DE_RANGO | RF-006 |
| lineas[].productoId | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir y estar activo | PRODUCTO_INACTIVO / RECURSO_NO_ENCONTRADO | RF-006 |
| lineas[].cantidad | decimal | requerido | hasta 3 decimales | mayor que 0, máximo 999 999,999 | ninguna | no aplica | CAMPO_FUERA_DE_RANGO | RF-006 |
| lineas[].costoUnitario | decimal | requerido | hasta 4 decimales | mayor que 0 | se rechaza separador de miles | no aplica | CAMPO_FUERA_DE_RANGO | RF-006 |
| lineas[].codigoLote | string | requerido, no vacío | alfanumérico, guion y punto | 1 a 40 caracteres | recorte y mayúsculas | junto con producto, vencimiento y costo determina si se acumula en un lote existente o se crea uno nuevo | CAMPO_REQUERIDO | RF-006 más docs/persistencia/modelo.md |
| lineas[].fechaVencimiento | fecha de calendario | requerido | `AAAA-MM-DD` | **posterior a la fecha de hoy** | ninguna | ingresar mercadería ya vencida se rechaza | LOTE_VENCIMIENTO_PASADO | RF-006: regla aprobada por el usuario |

### Reglas cruzadas
- `Deriva de RF-006`: el total de la compra es la suma de `cantidad × costoUnitario` de sus líneas; si el total enviado no coincide con el calculado, gana el calculado y no se acepta un total arbitrario.
- `Deriva de RF-006 y RF-008`: la compra, la creación o incremento de cada lote y la escritura de cada movimiento de kardex ocurren en **una sola transacción**. Si algo falla, no queda ni la compra ni el stock (RNF-003).
- `Deriva de docs/persistencia/modelo.md`: una línea cuyo producto, código de lote, vencimiento y costo unitario coinciden con un lote existente **acumula** sobre ese lote; si el costo difiere, se crea un lote nuevo, para que el costeo de RF-021 siga siendo real.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
