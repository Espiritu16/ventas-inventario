# Contrato — Servicios de dominio

Autoridad: Arquitectura. Versión del contrato: v1.

**Este es el contrato entre backend y frontend.** Con Livewire no hay una API
HTTP entre ambos: los componentes de la interfaz invocan directamente los
servicios de dominio en el mismo proceso. Por eso lo que hace posible el trabajo
en paralelo no es una ruta, sino la firma de estos métodos.

Reglas que lo gobiernan:

- **`implementation-backend` es el dueño**: implementa estos servicios y puede proponer cambios de firma, que Arquitectura aprueba.
- **`implementation-frontend` los consume**: los invoca desde sus componentes y **no cambia una firma**. Si necesita una distinta, escala a Arquitectura y su sprint queda bloqueado hasta la aprobación.
- Cambiar el nombre de un método, sus parámetros, su retorno o los errores que produce es un cambio **incompatible**: sube la versión de este contrato y reabre los RFC que lo consumen.
- Agregar un método nuevo, o un parámetro opcional al final, no es incompatible.
- Los códigos de error son los de `docs/errores/manejo-errores.md`. Un servicio **nunca corrige la entrada en silencio**: rechaza con su código.
- Toda operación que escribe es atómica: o se aplica completa, o no deja rastro.

Cada servicio vive en `app/Dominios/<Dominio>/`, según `laravel-estructura`.

---

## Usuarios — `UsuarioService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `autenticar(email, password)` | correo y contraseña | el usuario autenticado | CREDENCIALES_INVALIDAS | RF-001 |
| `crear(DatosUsuario)` | nombre, email, password, rol | el usuario creado | CAMPO_REQUERIDO, CAMPO_FORMATO_INVALIDO, DOCUMENTO_DUPLICADO | RF-002 |
| `actualizar(id, DatosUsuario parciales)` | campos a cambiar | el usuario actualizado | RECURSO_NO_ENCONTRADO, DOCUMENTO_DUPLICADO | RF-002 |
| `listar(Filtro)` | búsqueda y página | página de usuarios, sin el hash de contraseña | — | RF-002 |

## Catálogo — `CategoriaService`, `ProductoService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `CategoriaService::crear(DatosCategoria)` | nombre, descripción | la categoría creada | CAMPO_REQUERIDO, DOCUMENTO_DUPLICADO | RF-003 |
| `CategoriaService::actualizar(id, parciales)` | campos a cambiar | la categoría actualizada | RECURSO_NO_ENCONTRADO, DOCUMENTO_DUPLICADO | RF-003 |
| `CategoriaService::listar(incluirInactivas)` | booleano | categorías | — | RF-003 |
| `ProductoService::crear(DatosProducto)` | código, nombre, categoría, unidad, precios, stock mínimo | el producto creado | PRODUCTO_CODIGO_DUPLICADO, PRODUCTO_PRECIO_MAYOR_INVALIDO, RECURSO_NO_ENCONTRADO | RF-004 |
| `ProductoService::actualizar(id, parciales)` | campos a cambiar; el código no es modificable | el producto actualizado | RECURSO_NO_ENCONTRADO, PRODUCTO_PRECIO_MAYOR_INVALIDO | RF-004 |
| `ProductoService::buscar(texto, limite)` | texto de código o nombre | productos con su stock disponible y su precio menor y mayor | — | RF-011 |
| `ProductoService::listar(Filtro, actor)` | búsqueda, categoría, página | página de productos; **sin costo ni margen si el actor es vendedor** | — | RF-004 |

## Proveedores y clientes — `ProveedorService`, `ClienteService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `ProveedorService::crear(DatosProveedor)` | RUC, razón social, contacto | el proveedor creado | DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO | RF-005 |
| `ProveedorService::actualizar(id, parciales)` | campos a cambiar; el documento no es modificable | el proveedor actualizado | RECURSO_NO_ENCONTRADO | RF-005 |
| `ProveedorService::listar(Filtro)` | búsqueda, página | página de proveedores | — | RF-005 |
| `ClienteService::crear(DatosCliente)` | tipo y número de documento, nombre, dirección, contacto | el cliente creado | DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO, CAMPO_REQUERIDO | RF-010 |
| `ClienteService::actualizar(id, parciales)` | campos a cambiar; el documento no es modificable | el cliente actualizado | RECURSO_NO_ENCONTRADO | RF-010 |
| `ClienteService::buscar(texto, limite)` | documento o nombre | clientes | — | RF-010, RF-011 |

## Inventario — `InventarioService`

Es la **única puerta de escritura del stock**. Ninguna otra clase escribe en lotes
ni en el kardex; una prueba de arquitectura lo verifica (S-04-B/UT-02).

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `ingresar(productoId, cantidad, costoUnitario, codigoLote, fechaVencimiento, origen, usuarioId)` | datos de una línea de compra | el lote creado o incrementado | LOTE_VENCIMIENTO_PASADO, PRODUCTO_INACTIVO, RECURSO_NO_ENCONTRADO | RF-006 |
| `descontarPorVencimiento(productoId, cantidad, origen, usuarioId)` | producto y cantidad a sacar | reparto: lista de `(loteId, cantidad, costoUnitario)` en orden de vencimiento | STOCK_INSUFICIENTE, LOTE_VENCIDO | RF-012 |
| `ajustar(loteId, cantidadNueva, motivo, observacion, usuarioId)` | ajuste manual | el lote ajustado | AJUSTE_SIN_MOTIVO, AJUSTE_CANTIDAD_NEGATIVA, RECURSO_NO_ENCONTRADO | RF-009 |
| `consultarStock(Filtro, actor)` | búsqueda, categoría, página | productos con su stock disponible y sus lotes ordenados por vencimiento; **sin costo si el actor es vendedor** | — | RF-007 |
| `consultarKardex(productoId, desde, hasta, pagina)` | producto y rango | página de movimientos con origen y responsable | RECURSO_NO_ENCONTRADO, CAMPO_FUERA_DE_RANGO | RF-008 |
| `lotesPorVencer(dias)` | días de anticipación | lotes por vencer y vencidos con existencia, ordenados por urgencia | CAMPO_FUERA_DE_RANGO | RF-018 |
| `productosBajoMinimo()` | — | productos activos en o bajo su stock mínimo | — | RF-019 |

`descontarPorVencimiento` **no** cobra ni registra la venta: solo mueve stock.
Debe invocarse dentro de la transacción que abre `VentaService`.

## Compras — `CompraService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `registrar(DatosCompra, usuarioId)` | proveedor, documento, fecha y líneas con lote y vencimiento | la compra con sus lotes generados | COMPRA_SIN_LINEAS, COMPRA_DOCUMENTO_DUPLICADO, LOTE_VENCIMIENTO_PASADO, PRODUCTO_INACTIVO | RF-006 |
| `listar(Filtro)` | rango de fechas, proveedor, página | página de compras | CAMPO_FUERA_DE_RANGO | RF-006 |
| `ver(id)` | identificador | la compra con líneas y lotes | RECURSO_NO_ENCONTRADO | RF-006 |

## Ventas — `VentaService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `registrar(DatosVenta, usuarioId, claveIdempotencia)` | cliente, tipo de comprobante, medio de pago, líneas con producto, cantidad y tipo de precio | la venta con su reparto por lote y su comprobante en estado `PENDIENTE` | VENTA_SIN_LINEAS, STOCK_INSUFICIENTE, LOTE_VENCIDO, FACTURA_REQUIERE_RUC, BOLETA_REQUIERE_DOCUMENTO, TIPO_PRECIO_INVALIDO, SERIE_NO_CONFIGURADA, PRODUCTO_INACTIVO | RF-011, RF-012, RF-013 |
| `listar(Filtro, actor)` | rango, estado de comprobante, página | página de ventas; **acotada a las propias si el actor es vendedor** | CAMPO_FUERA_DE_RANGO | RF-011, RF-020 |
| `ver(id, actor)` | identificador | la venta con líneas, reparto por lote y estado del comprobante | RECURSO_NO_ENCONTRADO | RF-011 |
| `reporteVentas(desde, hasta)` | rango | total, desglose por comprobante y medio de pago, detalle | CAMPO_FUERA_DE_RANGO | RF-020 |
| `reporteUtilidad(desde, hasta, productoId)` | rango y producto opcional | ingreso, costo real por lote y utilidad, total y por producto | CAMPO_FUERA_DE_RANGO | RF-021 |

`registrar` es el método más delicado del sistema. En una sola transacción:
valida, descuenta por FEFO, escribe kardex, reserva correlativo y crea el
comprobante. Si algo falla, no queda nada. La clave de idempotencia evita que
una recarga o un doble clic registren dos ventas.

`ver(id, actor)` con una venta ajena para un vendedor produce
`RECURSO_NO_ENCONTRADO`, no `NO_AUTORIZADO`: no se revela que la venta existe.

## Comprobantes — `ComprobanteService`, `SerieComprobanteService`, `ResumenDiarioService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `SerieComprobanteService::crear(tipo, serie)` | tipo y serie | la serie creada | CAMPO_FORMATO_INVALIDO, DOCUMENTO_DUPLICADO | RF-014 |
| `SerieComprobanteService::listar()` | — | series con su correlativo actual | — | RF-014 |
| `SerieComprobanteService::reservarCorrelativo(tipo)` | tipo de comprobante | el siguiente correlativo, con la fila bloqueada | SERIE_NO_CONFIGURADA | RF-014 |
| `ComprobanteService::listar(Filtro)` | estado, rango, página | página de comprobantes, con pendientes y rechazados primero | CAMPO_FORMATO_INVALIDO | RF-016 |
| `ComprobanteService::reenviar(id)` | identificador | el comprobante encolado de nuevo | COMPROBANTE_NO_REENVIABLE, RECURSO_NO_ENCONTRADO | RF-016 |
| `ResumenDiarioService::generar(fechaReferencia)` | fecha | el resumen creado y encolado | BOLETA_YA_RESUMIDA, CAMPO_FUERA_DE_RANGO | RF-017 |
| `ResumenDiarioService::listar(desde, hasta, pagina)` | rango | página de resúmenes con su estado | — | RF-017 |

`reservarCorrelativo` es de uso interno del backend: lo invoca `VentaService`
dentro de su transacción. **El frontend nunca lo llama.**

## Emisión electrónica — interfaz `EmisorElectronico`

Frontera con SUNAT. Ningún dominio conoce Greenter; solo esta interfaz.

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `enviarComprobante(comprobante)` | el comprobante a emitir | resultado: aceptado con su CDR, o rechazado con código y mensaje de SUNAT | SUNAT_NO_DISPONIBLE, SUNAT_RECHAZO, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO | RF-015 |
| `enviarResumen(resumen)` | el resumen de boletas | el identificador de consulta que devuelve SUNAT | SUNAT_NO_DISPONIBLE, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO | RF-017 |
| `consultarResumen(ticket)` | identificador de consulta | el resultado del resumen | SUNAT_NO_DISPONIBLE | RF-017 |
| `diasParaVencimientoCertificado()` | — | días restantes del certificado | CERTIFICADO_NO_DISPONIBLE | RNF-005 |

**Solo los trabajos en segundo plano invocan esta interfaz.** Ni la venta ni
ningún componente de la interfaz la llaman directamente: eso rompería la
garantía de que la caja no depende de SUNAT (RNF-002).

## Auditoría — `AuditoriaService`

| Método | Entrada | Devuelve | Errores | Deriva de |
|---|---|---|---|---|
| `registrar(entidad, entidadId, accion, valoresAnteriores, valoresNuevos, usuarioId, origen)` | datos del cambio | nada | — | RNF-004 |

Los valores se arman con **lista explícita de campos auditables por entidad**,
nunca serializando el objeto completo, para que un campo sensible nuevo no quede
expuesto por omisión.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
