# Modelo de persistencia — ventas-inventario

Autoridad: Arquitectura. Motor: PostgreSQL (ver [ADR-0001](../decisiones/0001-motor-de-base-de-datos.md)).

## Convenciones

- **Instantes en UTC.** Todo campo que representa un momento usa `timestamptz`; la conversión a hora de Lima ocurre al mostrar o al calcular con ella, nunca al guardar.
- **Fechas sin hora** (vencimiento de lote, fecha de emisión del comprobante) usan `date`: son fechas de calendario, no instantes, y no se convierten de zona.
- **Dinero y cantidades** usan `numeric` con precisión explícita, nunca punto flotante. Importes: `numeric(12,2)`. Costos y precios unitarios: `numeric(12,4)`, para no perder precisión antes del redondeo final. Cantidades: `numeric(12,3)`.
- **Campos de tiempo del ORM**: `created_at`, `updated_at`, `deleted_at`, en snake_case, porque es lo que Eloquent espera por mecanismo propio del framework.
- **Borrado**: los maestros del dominio (`Usuario`, `Categoria`, `Producto`, `Proveedor`, `Cliente`) usan borrado lógico mediante `activo` o `deleted_at`; nunca borrado físico, porque están referenciados por el historial. Compras, ventas, lotes, movimientos y comprobantes **no se borran de ninguna forma**.
- **Entidad en singular, tabla en plural**, ambas en español.

---

## Entidad: Usuario (tabla: usuarios) — deriva de RF-001, RF-002
- Campos: `id` (PK), `nombre` (varchar(120), NOT NULL), `email` (varchar(150), NOT NULL, UNIQUE), `password` (varchar(255), NOT NULL — hash, nunca la contraseña), `rol` (varchar(20), NOT NULL, CHECK IN ('administrador','vendedor')), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Relaciones: 1:N con Compra, Venta, MovimientoInventario
- Índices: `email` (unique)
- Migraciones relacionadas: MIG-001

## Entidad: Categoria (tabla: categorias) — deriva de RF-003
- Campos: `id` (PK), `nombre` (varchar(80), NOT NULL, UNIQUE), `descripcion` (varchar(255), nullable), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Relaciones: 1:N con Producto
- Índices: `nombre` (unique)
- Migraciones relacionadas: MIG-002

## Entidad: Producto (tabla: productos) — deriva de RF-004, RF-007, RF-019
- Campos: `id` (PK), `codigo` (varchar(40), NOT NULL, UNIQUE), `nombre` (varchar(150), NOT NULL), `categoria_id` (FK → categorias.id, NOT NULL, ON DELETE RESTRICT), `unidad_medida` (varchar(10), NOT NULL, default 'NIU' — código de unidad exigido por SUNAT en el comprobante), `precio_menor` (numeric(12,4), NOT NULL, CHECK > 0), `precio_mayor` (numeric(12,4), NOT NULL, CHECK > 0), `stock_minimo` (numeric(12,3), NOT NULL, default 0, CHECK >= 0), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Restricción de negocio: CHECK `precio_mayor <= precio_menor` (RF-004)
- Relaciones: N:1 con Categoria; 1:N con Lote, DetalleCompra, DetalleVenta
- Índices: `codigo` (unique), `categoria_id`, índice de búsqueda por `nombre` para la caja
- Migraciones relacionadas: MIG-002

## Entidad: Proveedor (tabla: proveedores) — deriva de RF-005
- Campos: `id` (PK), `tipo_documento` (varchar(2), NOT NULL, CHECK IN ('6') — RUC), `numero_documento` (varchar(11), NOT NULL), `razon_social` (varchar(200), NOT NULL), `direccion` (varchar(255), nullable), `telefono` (varchar(20), nullable), `email` (varchar(150), nullable), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Relaciones: 1:N con Compra
- Índices: `(tipo_documento, numero_documento)` (unique)
- Migraciones relacionadas: MIG-003

## Entidad: Compra (tabla: compras) — deriva de RF-006
- Campos: `id` (PK), `proveedor_id` (FK → proveedores.id, NOT NULL, ON DELETE RESTRICT), `tipo_documento` (varchar(2), NOT NULL — tipo del documento del proveedor), `serie_documento` (varchar(4), NOT NULL), `numero_documento` (varchar(8), NOT NULL), `fecha_emision` (date, NOT NULL), `total` (numeric(12,2), NOT NULL, CHECK > 0), `usuario_id` (FK → usuarios.id, NOT NULL, ON DELETE RESTRICT), `created_at`, `updated_at`
- Relaciones: N:1 con Proveedor y Usuario; 1:N con DetalleCompra
- Índices: `(proveedor_id, tipo_documento, serie_documento, numero_documento)` (unique — RF-006), `fecha_emision`
- Migraciones relacionadas: MIG-004

## Entidad: DetalleCompra (tabla: detalle_compras) — deriva de RF-006
- Campos: `id` (PK), `compra_id` (FK → compras.id, NOT NULL, ON DELETE RESTRICT), `producto_id` (FK → productos.id, NOT NULL, ON DELETE RESTRICT), `cantidad` (numeric(12,3), NOT NULL, CHECK > 0), `costo_unitario` (numeric(12,4), NOT NULL, CHECK > 0), `codigo_lote` (varchar(40), NOT NULL), `fecha_vencimiento` (date, NOT NULL), `lote_id` (FK → lotes.id, NOT NULL, ON DELETE RESTRICT — el lote que esta línea creó o incrementó), `created_at`, `updated_at`
- Relaciones: N:1 con Compra, Producto y Lote
- Índices: `compra_id`, `producto_id`
- Migraciones relacionadas: MIG-004

## Entidad: Lote (tabla: lotes) — deriva de RF-006, RF-007, RF-012, RF-018
- Campos: `id` (PK), `producto_id` (FK → productos.id, NOT NULL, ON DELETE RESTRICT), `codigo_lote` (varchar(40), NOT NULL), `fecha_vencimiento` (date, NOT NULL), `cantidad_actual` (numeric(12,3), NOT NULL, CHECK >= 0 — la restricción que impide vender más de lo que hay), `costo_unitario` (numeric(12,4), NOT NULL, CHECK > 0), `created_at`, `updated_at`
- Relaciones: N:1 con Producto; 1:N con DetalleVentaLote y MovimientoInventario
- Índices: `(producto_id, codigo_lote, fecha_vencimiento, costo_unitario)` (unique — dos ingresos del mismo lote con el mismo costo se acumulan en la misma fila; un costo distinto abre un lote nuevo, que es lo que permite el costeo real de RF-021), `(producto_id, fecha_vencimiento)` (índice de soporte del descuento FEFO de RF-012), `fecha_vencimiento` (índice de soporte de la alerta de RF-018)
- Migraciones relacionadas: MIG-004

## Entidad: MovimientoInventario (tabla: movimientos_inventario) — deriva de RF-008, RF-009
- Campos: `id` (PK), `lote_id` (FK → lotes.id, NOT NULL, ON DELETE RESTRICT), `producto_id` (FK → productos.id, NOT NULL, ON DELETE RESTRICT — desnormalizado a propósito, para consultar el kardex de un producto sin unir por lotes), `tipo` (varchar(10), NOT NULL, CHECK IN ('ingreso','salida','ajuste')), `cantidad` (numeric(12,3), NOT NULL, CHECK <> 0 — positiva en ingreso, negativa en salida, de cualquier signo en ajuste), `costo_unitario` (numeric(12,4), NOT NULL), `motivo` (varchar(30), nullable — obligatorio cuando `tipo = 'ajuste'`, CHECK que lo exige), `origen_tipo` (varchar(20), NOT NULL, CHECK IN ('compra','venta','ajuste')), `origen_id` (bigint, NOT NULL — referencia lógica al documento de origen; sin FK, porque apunta a tablas distintas según `origen_tipo`), `usuario_id` (FK → usuarios.id, NOT NULL, ON DELETE RESTRICT), `created_at`
- Inmutabilidad: append-only (RNF-004). La aplicación no tiene privilegio de `UPDATE` ni `DELETE` sobre esta tabla; una corrección se registra como un movimiento de ajuste nuevo. No lleva `updated_at`.
- Relaciones: N:1 con Lote, Producto y Usuario
- Índices: `(producto_id, created_at)`, `lote_id`, `(origen_tipo, origen_id)`
- Migraciones relacionadas: MIG-005

## Entidad: Cliente (tabla: clientes) — deriva de RF-010
- Campos: `id` (PK), `tipo_documento` (varchar(2), NOT NULL, CHECK IN ('0','1','4','6') — sin documento, DNI, carné de extranjería, RUC, según el catálogo de SUNAT), `numero_documento` (varchar(15), nullable — nulo solo cuando `tipo_documento = '0'`), `nombre` (varchar(200), NOT NULL), `direccion` (varchar(255), nullable), `telefono` (varchar(20), nullable), `email` (varchar(150), nullable), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Restricción de negocio: CHECK que exige `numero_documento` presente cuando `tipo_documento <> '0'`
- Relaciones: 1:N con Venta
- Índices: índice único parcial sobre `(tipo_documento, numero_documento)` donde `numero_documento IS NOT NULL` — **corregido el 2026-08-19**: el motivo escrito antes decía que la parcialidad "permite varias ventas a público general sin documento sin romper la unicidad", y **eso no es exacto**. PostgreSQL trata dos `NULL` como distintos en cualquier índice único, así que varios clientes sin documento conviven **con la condición parcial y sin ella**; el comportamiento es idéntico y ninguna prueba funcional puede distinguirlos. Lo que la parcialidad sí aporta es que **no indexa las filas sin documento** y que **deja la garantía escrita en la definición del índice**, en vez de depender de cómo el motor trate los nulos — que es configurable desde PostgreSQL 15 con `NULLS NOT DISTINCT`. La decisión sigue siendo la correcta; lo que estaba mal era la razón. Lo detectó `implementation-backend` en S-03-B, porque mutar el índice no hizo fallar ninguna prueba y fue a averiguar por qué en vez de darlo por cubierto. Se protege con una prueba **estructural** sobre `pg_indexes`, deliberadamente: una de comportamiento no comprobaría nada
- Migraciones relacionadas: MIG-006

## Entidad: Venta (tabla: ventas) — deriva de RF-011, RF-020
- Campos: `id` (PK), `cliente_id` (FK → clientes.id, NOT NULL, ON DELETE RESTRICT), `usuario_id` (FK → usuarios.id, NOT NULL, ON DELETE RESTRICT), `fecha` (timestamptz, NOT NULL), `subtotal` (numeric(12,2), NOT NULL, CHECK > 0 — base imponible), `igv` (numeric(12,2), NOT NULL, CHECK >= 0), `total` (numeric(12,2), NOT NULL, CHECK > 0), `metodo_pago` (varchar(15), NOT NULL, CHECK IN ('efectivo','tarjeta','billetera')), `created_at`, `updated_at`
- Restricción de negocio: CHECK `subtotal + igv = total` (RF-011)
- Relaciones: N:1 con Cliente y Usuario; 1:N con DetalleVenta; 1:1 con Comprobante
- Índices: `fecha`, `usuario_id`
- Migraciones relacionadas: MIG-007

## Entidad: DetalleVenta (tabla: detalle_ventas) — deriva de RF-011
- Campos: `id` (PK), `venta_id` (FK → ventas.id, NOT NULL, ON DELETE RESTRICT), `producto_id` (FK → productos.id, NOT NULL, ON DELETE RESTRICT), `cantidad` (numeric(12,3), NOT NULL, CHECK > 0), `tipo_precio` (varchar(6), NOT NULL, CHECK IN ('menor','mayor')), `precio_unitario` (numeric(12,4), NOT NULL, CHECK > 0 — el precio efectivamente aplicado, copiado al momento de la venta), `importe` (numeric(12,2), NOT NULL, CHECK > 0), `created_at`, `updated_at`
- Relaciones: N:1 con Venta y Producto; 1:N con DetalleVentaLote
- Índices: `venta_id`, `producto_id`
- Migraciones relacionadas: MIG-007

## Entidad: DetalleVentaLote (tabla: detalle_venta_lotes) — deriva de RF-012, RF-021
- Campos: `id` (PK), `detalle_venta_id` (FK → detalle_ventas.id, NOT NULL, ON DELETE RESTRICT), `lote_id` (FK → lotes.id, NOT NULL, ON DELETE RESTRICT), `cantidad` (numeric(12,3), NOT NULL, CHECK > 0), `costo_unitario` (numeric(12,4), NOT NULL — el costo del lote al momento de la salida, congelado acá para que el reporte de utilidad no dependa de que el lote siga existiendo igual)
- Relaciones: N:1 con DetalleVenta y Lote
- Índices: `detalle_venta_id`, `lote_id`
- Migraciones relacionadas: MIG-007

## Entidad: SerieComprobante (tabla: series_comprobante) — deriva de RF-014
- Campos: `id` (PK), `tipo_comprobante` (varchar(2), NOT NULL, CHECK IN ('01','03') — factura, boleta), `serie` (varchar(4), NOT NULL), `correlativo_actual` (integer, NOT NULL, default 0, CHECK >= 0), `activo` (boolean, NOT NULL, default true), `created_at`, `updated_at`
- Concurrencia: el correlativo se reserva con `SELECT ... FOR UPDATE` sobre esta fila dentro de la transacción de la venta. Es el punto de serialización que garantiza RF-014 y RNF-003; no se usa `MAX(correlativo)+1`, que no serializa nada.
- Relaciones: 1:N con Comprobante
- Índices: `(tipo_comprobante, serie)` (unique)
- Migraciones relacionadas: MIG-008

## Entidad: Comprobante (tabla: comprobantes) — deriva de RF-013, RF-015, RF-016
- Campos: `id` (PK), `venta_id` (FK → ventas.id, NOT NULL, UNIQUE, ON DELETE RESTRICT), `serie_comprobante_id` (FK → series_comprobante.id, NOT NULL, ON DELETE RESTRICT), `tipo_comprobante` (varchar(2), NOT NULL, CHECK IN ('01','03')), `serie` (varchar(4), NOT NULL), `correlativo` (integer, NOT NULL, CHECK > 0), `fecha_emision` (date, NOT NULL), `estado` (varchar(12), NOT NULL, CHECK IN ('PENDIENTE','ENVIADO','ACEPTADO','RECHAZADO'), default 'PENDIENTE'), `intentos` (integer, NOT NULL, default 0), `enviado_en` (timestamptz, nullable), `respondido_en` (timestamptz, nullable), `hash_xml` (varchar(100), nullable), `ruta_xml` (varchar(255), nullable), `ruta_cdr` (varchar(255), nullable), `codigo_sunat` (varchar(10), nullable), `mensaje_sunat` (varchar(500), nullable), `resumen_diario_id` (FK → resumenes_diarios.id, nullable, ON DELETE RESTRICT), `created_at`, `updated_at`
- Estados y transiciones: los definidos y aprobados en RF-013. Ninguna otra transición está permitida.
- Idempotencia del envío: la combinación `(tipo_comprobante, serie, correlativo)` es única, y un comprobante solo se envía como documento nuevo mientras su estado es `PENDIENTE`. El trabajo de envío toma la fila con bloqueo antes de enviar, de modo que dos ejecuciones simultáneas no producen dos envíos (RF-015).
- Relaciones: 1:1 con Venta; N:1 con SerieComprobante y ResumenDiario
- Índices: `(tipo_comprobante, serie, correlativo)` (unique), `estado`, `fecha_emision`, `venta_id` (unique)
- Migraciones relacionadas: MIG-008

## Entidad: ResumenDiario (tabla: resumenes_diarios) — deriva de RF-017
- Campos: `id` (PK), `fecha_referencia` (date, NOT NULL — el día cuyas boletas resume), `correlativo` (integer, NOT NULL — número de resumen de ese día), `estado` (varchar(12), NOT NULL, CHECK IN ('PENDIENTE','ENVIADO','ACEPTADO','RECHAZADO'), default 'PENDIENTE'), `ticket` (varchar(50), nullable — identificador que devuelve SUNAT para consultar el resultado), `ruta_xml` (varchar(255), nullable), `ruta_cdr` (varchar(255), nullable), `codigo_sunat` (varchar(10), nullable), `mensaje_sunat` (varchar(500), nullable), `enviado_en` (timestamptz, nullable), `created_at`, `updated_at`
- Regla: una boleta se incluye en un único resumen aceptado. Se garantiza porque `comprobantes.resumen_diario_id` se fija al construir el resumen y solo se consideran boletas con ese campo nulo.
- Relaciones: 1:N con Comprobante
- Índices: `(fecha_referencia, correlativo)` (unique), `estado`
- Migraciones relacionadas: MIG-008

## Entidad: Auditoria (tabla: auditorias) — política de documentacion-estructura
- Campos: `id` (PK), `entidad` (varchar(50), NOT NULL), `entidad_id` (bigint, NOT NULL), `accion` (varchar(12), NOT NULL, CHECK IN ('crear','actualizar','eliminar')), `usuario_id` (bigint, nullable — **sin FK real**, referencia lógica, para que la fila nunca se modifique por un cambio en usuarios), `origen` (varchar(40), NOT NULL, default 'usuario' — `usuario`, `job:<nombre>`, `comando:<nombre>`), `fecha` (timestamptz, NOT NULL), `valores_anteriores` (jsonb, nullable), `valores_nuevos` (jsonb, nullable)
- Append-only: la aplicación solo tiene `INSERT` y `SELECT` sobre esta tabla. Ninguna FK apunta hacia ella.
- Redacción: `valores_anteriores` y `valores_nuevos` se escriben con **lista explícita de campos auditables por entidad**, nunca serializando el objeto completo. `password` y cualquier dato del certificado están excluidos por construcción.
- Qué se audita en esta versión: cambios de precio de producto, ajustes de inventario, alta y cambio de rol de usuarios, y cambios de configuración de series.
- Retención: 5 años, alineada con el plazo de conservación de comprobantes. La purga posterior es un proceso explícito, nunca un borrado desde el código de la aplicación.
- Índices: `(entidad, entidad_id)`, `fecha`
- Migraciones relacionadas: MIG-009

## Entidad: LogError (tabla: log_errores) — política de documentacion-estructura
- Campos: `id` (PK), `mensaje` (text, NOT NULL), `stack_trace` (text, nullable), `contexto` (jsonb, nullable), `severidad` (varchar(10), NOT NULL, CHECK IN ('info','warning','error','critical')), `fecha` (timestamptz, NOT NULL), `resuelto` (boolean, NOT NULL, default false), `trace_id` (varchar(64), NOT NULL)
- `resuelto` significa: la causa fue corregida y desplegada. Solo el administrador puede marcarlo.
- Saneamiento: `contexto` se arma con lista explícita de campos (`ruta`, `metodo`, `usuario_id`, `comprobante_id`), nunca con la petición completa. `mensaje` y `stack_trace` pasan por enmascarado de patrones de secretos antes de guardarse — en particular, nada del certificado digital ni de su clave puede quedar registrado (RNF-005, RNF-014).
- Canal independiente: todo error se escribe además a la salida estándar del proceso en formato estructurado, porque si la falla es la propia base de datos, esta tabla no está disponible. Que ese canal se recolecte de verdad debe verificarse en el entorno real de despliegue; mientras no se verifique, esta tabla es el único registro efectivo.
- Acceso restringido al administrador; misma retención y política de acceso que `Auditoria`.
- Índices: `fecha`, `severidad`, `trace_id`
- Migraciones relacionadas: MIG-009

---

## Plan de migraciones

| ID lógico | Orden/depende de | Cambio de schema | Datos/backfill | Reversibilidad | Artefacto esperado | Estado/fuente |
|---|---|---|---|---|---|---|
| MIG-001 | primera | tabla `usuarios` con su CHECK de rol | usuario administrador inicial vía seeder, no vía migración | rollback de tablas vacías | migración de Laravel; ruta pendiente hasta generarla | derivado de RF-001, RF-002; propuesto |
| MIG-002 | depende de MIG-001 | tablas `categorias` y `productos`, con CHECK de precios e índices de búsqueda | no aplica — proyecto nuevo | rollback de tablas vacías | migración de Laravel | derivado de RF-003, RF-004; propuesto |
| MIG-003 | depende de MIG-001 | tabla `proveedores` con unicidad de documento | no aplica | rollback de tablas vacías | migración de Laravel | derivado de RF-005; propuesto |
| MIG-004 | depende de MIG-002, MIG-003 | tablas `lotes`, `compras` y `detalle_compras`, con el CHECK de cantidad no negativa del lote | no aplica | rollback de tablas vacías | migración de Laravel | derivado de RF-006, RF-007; propuesto |
| MIG-005 | depende de MIG-004 | tabla `movimientos_inventario` y revocación del privilegio de UPDATE/DELETE de la aplicación sobre ella | no aplica | rollback de tabla vacía; la revocación de privilegios se revierte por separado | migración de Laravel más instrucción explícita de permisos | derivado de RF-008, RF-009, RNF-004; propuesto |
| MIG-006 | depende de MIG-001 | tabla `clientes` con índice único parcial | cliente "público general" vía seeder | rollback de tabla vacía | migración de Laravel | derivado de RF-010; propuesto |
| MIG-007 | depende de MIG-004, MIG-006 | tablas `ventas`, `detalle_ventas` y `detalle_venta_lotes` con sus CHECK de importes | no aplica | rollback de tablas vacías | migración de Laravel | derivado de RF-011, RF-012; propuesto |
| MIG-008 | depende de MIG-007 | tablas `series_comprobante`, `resumenes_diarios` y `comprobantes`, con la unicidad de serie y correlativo | series iniciales vía seeder | rollback de tablas vacías | migración de Laravel | derivado de RF-013, RF-014, RF-015, RF-017; propuesto |
| MIG-009 | depende de MIG-001 | tablas `auditorias` y `log_errores`, y privilegios restringidos sobre `auditorias` | no aplica | rollback de tablas vacías | migración de Laravel más instrucción explícita de permisos | política de documentacion-estructura; propuesto |

Cada migración se genera con el mecanismo nativo de Laravel y su ruta real se
registra en esta tabla y en la entidad afectada al crearse. Ninguna migración
se ejecuta sobre datos reales sin autorización explícita, según `AGENTS.md`.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, ejercida por el agente sobre el diseño aprobado por Kevin Espíritu — fecha: 2026-08-19
