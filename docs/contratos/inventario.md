# Contrato — Inventario, kardex y alertas

Autoridad: Arquitectura. Versión del contrato: v1.

## GET /inventario
- Ruta real: GET /inventario
- Query params: `buscar?: string (opcional)`, `categoriaId?: entero (opcional)`, `soloConStock?: boolean (opcional, default false)`, `pagina?: entero (opcional, default 1)`
- Response éxito: por producto, el stock disponible (suma de lotes no vencidos con existencia) y el detalle de sus lotes con cantidad y fecha de vencimiento, **ordenados por vencimiento más próximo**. Los lotes vencidos se listan marcados como tales y no suman al disponible. **Para el rol `vendedor` no se incluye el costo unitario del lote**
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`
- Soporte de índices: el orden por vencimiento usa `(producto_id, fecha_vencimiento)`

## GET /inventario/kardex
- Query params: `productoId: entero (requerido)`, `desde?: fecha (opcional, default hace 30 días)`, `hasta?: fecha (opcional, default hoy)`, `pagina?: entero (opcional, default 1)`
- Response éxito: movimientos del producto ordenados por fecha descendente, desempatados por `id`, con tipo, lote, cantidad, costo, documento de origen y usuario responsable
- Errores: CAMPO_REQUERIDO, RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`
- Soporte de índices: `(producto_id, created_at)`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| productoId (query) | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir | RECURSO_NO_ENCONTRADO | RF-008 |
| desde (query) | fecha de calendario | opcional, default hace 30 días | `AAAA-MM-DD` | no futura | se interpreta en hora de Lima y se convierte a UTC para consultar | no puede ser posterior a `hasta` | CAMPO_FUERA_DE_RANGO | RF-008 |
| hasta (query) | fecha de calendario | opcional, default hoy | `AAAA-MM-DD` | no futura | igual que `desde` | rango máximo de 366 días | CAMPO_FUERA_DE_RANGO | derivado: límite propuesto |

## POST /inventario/ajustes
- Ruta real: POST /inventario/ajustes
- Request: `{ loteId: entero (requerido), cantidadNueva: decimal (requerido), motivo: string (requerido), observacion?: string (opcional) }`
- Response éxito: lote ajustado y movimiento de kardex de tipo `ajuste` registrado con el usuario responsable
- Idempotencia: no aplica — cada ajuste es un hecho distinto, aunque coincida en valores con otro anterior
- Concurrencia: la fila del lote se toma con bloqueo dentro de la transacción, para que un ajuste y una venta simultáneos no se pisen
- Errores: CAMPO_REQUERIDO, AJUSTE_SIN_MOTIVO, AJUSTE_CANTIDAD_NEGATIVA, RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| loteId (body) | entero (identificador) | requerido | entero positivo | no aplica | ninguna | debe existir | RECURSO_NO_ENCONTRADO | RF-009 |
| cantidadNueva (body) | decimal | requerido, no null | hasta 3 decimales | mayor o igual que 0 | ninguna | no puede dejar el lote negativo; la diferencia contra la cantidad actual es lo que se registra como movimiento | AJUSTE_CANTIDAD_NEGATIVA | RF-009 |
| motivo (body) | enum | requerido, no null | uno de `merma`, `rotura`, `vencimiento`, `error_conteo` | no aplica | ninguna | no aplica | AJUSTE_SIN_MOTIVO | RF-009 |
| observacion (body) | string | opcional, default null | texto libre | máximo 255 caracteres | recorte | requerida cuando el motivo es `error_conteo`, para que quede explicado el descuadre | CAMPO_REQUERIDO | derivado: regla propuesta |

## GET /panel
- Ruta real: GET /panel
- Query params: `diasPorVencer?: entero (opcional, default 30)`
- Response éxito: dos listados — lotes por vencer dentro del plazo y ya vencidos con existencia, ordenados por urgencia (RF-018); y productos cuyo stock disponible es menor o igual a su stock mínimo, excluyendo inactivos (RF-019). **El rol `vendedor` ve ambos listados sin indicadores de utilidad**
- Errores: NO_AUTENTICADO, CAMPO_FUERA_DE_RANGO
- Autenticación: requerida, roles `administrador` y `vendedor`
- Soporte de índices: la alerta de vencimiento usa el índice de `fecha_vencimiento`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| diasPorVencer (query) | entero | opcional, default 30 | entero positivo | de 1 a 365 | ninguna | no aplica | CAMPO_FUERA_DE_RANGO | RF-018 |

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
