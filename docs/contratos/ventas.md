# Contrato — Ventas

Autoridad: Arquitectura. Versión del contrato: v1.

Es la operación más delicada del sistema: descuenta stock por vencimiento,
reserva un correlativo tributario y crea el comprobante, todo en una sola
transacción.

## POST /ventas
- Ruta real: POST /ventas
- Request:
  ```
  {
    clienteId: entero (requerido),
    tipoComprobante: string (requerido),
    metodoPago: string (requerido),
    lineas: [ { productoId: entero (requerido), cantidad: decimal (requerido),
                tipoPrecio: string (requerido) } ] (requerido, al menos una)
  }
  ```
- Response éxito: venta registrada con su comprobante en estado `PENDIENTE`, listo para imprimir. Incluye el detalle del reparto por lote de cada línea
- Idempotencia: **requerida**. La pantalla de caja envía una clave de operación (`Idempotency-Key`) generada al abrir la venta; si la misma clave llega otra vez —doble clic, recarga, reintento del navegador— se devuelve la venta original en vez de registrar una segunda. Una venta duplicada descontaría stock real y consumiría un correlativo tributario, lo cual es inaceptable. Huella: cliente, tipo de comprobante, método de pago y líneas. Retención de la clave: 24 horas; una clave expirada que vuelve a llegar se rechaza, no se reprocesa
- Concurrencia: no aplica en el sentido de edición concurrente — una venta se crea, no se edita. La competencia por lotes y por el correlativo se resuelve con bloqueo de fila dentro de la transacción, no con versión optimista
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, VENTA_SIN_LINEAS, TIPO_PRECIO_INVALIDO, STOCK_INSUFICIENTE, LOTE_VENCIDO, PRODUCTO_INACTIVO, FACTURA_REQUIERE_RUC, BOLETA_REQUIERE_DOCUMENTO, SERIE_NO_CONFIGURADA, CORRELATIVO_EN_CONFLICTO, RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| Idempotency-Key (header) | string | requerido, no vacío | UUID versión 4 | 36 caracteres | minúsculas | debe ser única por venta; reutilizarla con una huella distinta se rechaza | CAMPO_FORMATO_INVALIDO | derivado de RF-011 y RNF-003 |
| clienteId (body) | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir y estar activo | RECURSO_NO_ENCONTRADO | RF-010, RF-011 |
| tipoComprobante (body) | enum | requerido | `01` (factura) o `03` (boleta) | no aplica | ninguna | `01` exige que el cliente tenga RUC y dirección; `03` con importe sobre el tope exige documento del cliente | FACTURA_REQUIERE_RUC / BOLETA_REQUIERE_DOCUMENTO | RF-010, RF-011; catálogo de SUNAT |
| metodoPago (body) | enum | requerido | `efectivo`, `tarjeta` o `billetera` | no aplica | ninguna | se registra, no se cobra: el cobro ocurre fuera del sistema | CAMPO_FORMATO_INVALIDO | RF-011 |
| lineas (body) | colección | requerido | no aplica | de 1 a 100 elementos | ninguna | una venta sin líneas se rechaza | VENTA_SIN_LINEAS / CAMPO_FUERA_DE_RANGO | RF-011 |
| lineas[].productoId | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir y estar activo | PRODUCTO_INACTIVO / RECURSO_NO_ENCONTRADO | RF-011 |
| lineas[].cantidad | decimal | requerido | hasta 3 decimales | mayor que 0, máximo 999 999,999 | ninguna | debe haber stock disponible no vencido suficiente | STOCK_INSUFICIENTE | RF-011, RF-012 |
| lineas[].tipoPrecio | enum | requerido | `menor` o `mayor` | no aplica | minúsculas | determina qué precio del producto se copia a la línea | TIPO_PRECIO_INVALIDO | RF-011 |

### Reglas cruzadas
- `Deriva de RF-011`: el precio unitario de cada línea se **copia del producto** al momento de la venta según `tipoPrecio`; no se acepta un precio enviado por el cliente de la operación. El importe de línea es `cantidad × precio unitario`, redondeado a 2 decimales.
- `Deriva de RF-011 y RNF-006`: el total es la suma de los importes de línea. La base imponible y el IGV se derivan del total, que ya incluye IGV al 18 %: `base = total / 1,18`, `IGV = total − base`, ambos redondeados a 2 decimales, de modo que `base + IGV = total` exactamente.
- `Deriva de RF-012`: cada línea se cubre tomando los lotes del producto **en orden de vencimiento más próximo**, entre los no vencidos con existencia, partiendo entre varios lotes si hace falta. Cada porción se registra con el costo del lote del que salió. Un lote vencido nunca se toma.
- `Deriva de RF-012`: si el disponible no alcanza, **la venta se rechaza completa**, sin descontar nada, informando la cantidad disponible en `detalle`. No se vende parcialmente.
- `Deriva de RF-010`: el tope de importe para emitir una boleta sin identificar al cliente es **S/ 700** y es configurable. Superado ese importe, la boleta exige documento del cliente. Valor **aprobado por el usuario el 2026-08-19**. Se mantiene configurable por variable de entorno, para poder ajustarlo si cambia la norma sin tocar código.
- `Deriva de RF-014 y RNF-003`: el correlativo se reserva tomando la fila de la serie con bloqueo dentro de la misma transacción. Si la serie del tipo elegido no existe o está inactiva, la venta se rechaza antes de tocar el stock.
- `Deriva de RNF-003`: venta, detalle, reparto por lote, descuento de lotes, movimientos de kardex, reserva de correlativo y creación del comprobante ocurren en **una sola transacción**. El envío a SUNAT queda fuera de ella (ver ADR-0003).

## GET /ventas
- Query params: `desde?: fecha (opcional, default hoy)`, `hasta?: fecha (opcional, default hoy)`, `estadoComprobante?: string (opcional)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por fecha descendente y desempatado por `id`, con el estado del comprobante de cada venta
- Condición de alcance: **el rol `vendedor` solo ve las ventas que él registró** (`venta.usuario_id == actor.id`). El filtro se aplica sobre ese subconjunto ya acotado, y también acota el total del paginado
- Errores: NO_AUTENTICADO, NO_AUTORIZADO, CAMPO_FUERA_DE_RANGO
- Autenticación: requerida, roles `administrador` y `vendedor`
- Soporte de índices: `fecha` y `usuario_id`

## GET /ventas/{id}
- Response éxito: la venta con sus líneas, el reparto por lote de cada línea y el estado de su comprobante. **Para el rol `vendedor` el reparto no incluye el costo unitario, ni del reparto ni del lote** (proyección acotada, igual que en `GET /productos` y `GET /inventario`)

> **Enmienda de Arquitectura, 2026-08-20 — el vendedor no ve el costo tampoco en sus propias ventas.**
> El sistema ya declaraba tres veces que el costo no es del vendedor: acotado en `GET /productos` ("sin columna de costo ni de margen"),
> acotado en `GET /inventario` ("sin costo unitario del lote"), y `GET /reportes/utilidad` —que existe precisamente para el costo real
> tomado del reparto— es solo de administrador. Esta ruta abría una **cuarta superficie sobre el mismo dato** y era la única sin acotar,
> con el costo literal junto al precio de venta en la misma respuesta y el margen derivable por resta.
>
> No era divergencia: el contrato no tenía cláusula de proyección, así que `implementation-backend` implementó lo que decía. La omisión era del contrato.
>
> **Se resuelve proyectando y no ampliando el permiso.** La alternativa —declarar que el vendedor sí ve el costo de las ventas que él hizo—
> obligaría a justificar por qué ahí sí y en catálogo e inventario no, y el margen es información del negocio, no dato que quien vende necesite
> para su trabajo: lo que necesita de su propia venta es qué vendió, a qué precio y en qué estado quedó el comprobante.
>
> **Hoy no es explotable** —ADR-0006 retiró el endpoint y ninguna pantalla consume ese método—, así que este es el mejor momento para cerrarlo.
> Debe estar implementado y fijado con una prueba **antes de que S-05-F pinte esa pantalla**. Lo detectó `qa` al validar S-05-B, y fue al contrato
> antes de calificarlo en vez de reportarlo como defecto de implementación.

- Condición de alcance: el rol `vendedor` solo accede a las propias; una venta ajena responde igual que una inexistente, para no revelar su existencia
- Errores: RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`

## GET /reportes/ventas
- Query params: `desde: fecha (requerido)`, `hasta: fecha (requerido)`
- Response éxito: total del período, desglose por tipo de comprobante y por método de pago, y detalle de ventas, distinguiendo las de comprobante rechazado (RF-020)
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## GET /reportes/utilidad
- Query params: `desde: fecha (requerido)`, `hasta: fecha (requerido)`, `productoId?: entero (opcional)`
- Response éxito: ingreso, costo real de lo vendido —tomado de `detalle_venta_lotes`, no de un promedio— y utilidad, en total y por producto (RF-021)
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
