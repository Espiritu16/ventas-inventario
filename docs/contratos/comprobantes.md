# Contrato — Comprobantes electrónicos y SUNAT

Autoridad: Arquitectura. Versión del contrato: v1.
Detalle de la dependencia externa: [docs/integraciones/sunat.md](../integraciones/sunat.md).

## GET /series-comprobante
- Response éxito: series configuradas por tipo, con su correlativo actual
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## POST /series-comprobante
- Request: `{ tipoComprobante: string (requerido), serie: string (requerido) }`
- Idempotencia: deduplicación mediante la unicidad de tipo y serie
- Errores: CAMPO_REQUERIDO, CAMPO_FORMATO_INVALIDO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| tipoComprobante (body) | enum | requerido | `01` (factura) o `03` (boleta) | no aplica | ninguna | no aplica | CAMPO_FORMATO_INVALIDO | RF-014; catálogo de SUNAT |
| serie (body) | string | requerido, no vacío | exactamente 4 caracteres: `F` seguido de 3 alfanuméricos para factura, `B` seguido de 3 para boleta | 4 caracteres | mayúsculas | el prefijo debe corresponder al tipo elegido | CAMPO_FORMATO_INVALIDO | RF-014; formato exigido por SUNAT |

El correlativo inicial es 0 y solo lo modifica el sistema al emitir. No existe
operación para fijarlo a mano: alterarlo rompería la garantía de RF-014.

## GET /comprobantes
- Query params: `estado?: string (opcional — `PENDIENTE`, `ENVIADO`, `ACEPTADO`, `RECHAZADO`)`, `desde?: fecha (opcional, default hace 7 días)`, `hasta?: fecha (opcional, default hoy)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado con los `PENDIENTE` y `RECHAZADO` primero y luego por fecha descendente, desempatado por `id`. Cada rechazado muestra el código y el mensaje exactos de SUNAT
- Errores: NO_AUTENTICADO, NO_AUTORIZADO, CAMPO_FORMATO_INVALIDO
- Autenticación: requerida, rol `administrador`
- Soporte de índices: `estado` y `fecha_emision`

## POST /comprobantes/{id}/reenviar
- Ruta real: POST /comprobantes/{id}/reenviar
- Path params: `id: entero`
- Request: sin cuerpo
- Response éxito: el comprobante queda encolado para un intento nuevo; su estado permanece `PENDIENTE` hasta que el proceso lo tome
- Idempotencia: **deduplicación**. Reenviar dos veces no produce dos documentos: el proceso toma la fila con bloqueo y solo actúa si sigue en `PENDIENTE`
- Errores: RECURSO_NO_ENCONTRADO, COMPROBANTE_NO_REENVIABLE, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`
- Regla de negocio: solo un comprobante en `PENDIENTE` se puede reenviar. Uno `ACEPTADO` ya está informado; uno `RECHAZADO` no se reintenta porque el rechazo es por datos y su corrección exige notas de crédito, fuera de alcance (RF-013, RF-016).

## job: enviar-comprobante
- Disparador: automático tras registrarse una venta, y por reintento programado
- Actor: `sistema`
- Efecto: arma el XML UBL 2.1, lo firma con el certificado, lo envía a SUNAT y aplica la transición correspondiente del ciclo de vida de RF-013
- Idempotencia: **requerida por diseño**. Antes de enviar, toma la fila del comprobante con bloqueo y verifica que siga en `PENDIENTE`. La unicidad de `(tipo, serie, correlativo)` impide que un mismo documento se emita dos veces aunque el proceso se ejecute en paralelo
- Reintentos: ante `SUNAT_NO_DISPONIBLE`, con esperas crecientes (1, 5, 15, 60 minutos y luego cada 6 horas), hasta 10 intentos; superados, el comprobante permanece `PENDIENTE` y queda visible en la pantalla de seguimiento para intervención manual. Ante `SUNAT_RECHAZO`, **ningún reintento**
- Errores: SUNAT_NO_DISPONIBLE, SUNAT_RECHAZO, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO, TRANSICION_COMPROBANTE_INVALIDA
- Resultado: no devuelve HTTP; el resultado queda en el estado del comprobante, en `codigo_sunat`/`mensaje_sunat` y en `LogError` cuando corresponde

## GET /resumenes-diarios
- Query params: `desde?: fecha (opcional, default hace 30 días)`, `hasta?: fecha (opcional, default hoy)`, `pagina?: entero (opcional, default 1)`
- Response éxito: resúmenes con su fecha de referencia, estado y resultado de SUNAT
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## POST /resumenes-diarios
- Request: `{ fechaReferencia: fecha (requerido) }`
- Response éxito: resumen creado y encolado para envío
- Idempotencia: **deduplicación** mediante la unicidad de `(fecha_referencia, correlativo)`; además, solo se incluyen boletas cuyo `resumen_diario_id` está vacío
- Errores: CAMPO_REQUERIDO, CAMPO_FUERA_DE_RANGO, BOLETA_YA_RESUMIDA, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| fechaReferencia (body) | fecha de calendario | requerido | `AAAA-MM-DD` | no futura; dentro de los últimos 7 días | ninguna — es fecha local de emisión, no instante | debe haber al menos una boleta de ese día sin resumen aceptado | CAMPO_FUERA_DE_RANGO / BOLETA_YA_RESUMIDA | RF-017; ventana de 7 días propuesta según el plazo de SUNAT |

## job: enviar-resumen-diario
- Disparador: programado una vez al día
- Actor: `sistema`
- Efecto: agrupa las boletas del día anterior aún no resumidas, envía el resumen, guarda el identificador de consulta que devuelve SUNAT y registra el resultado al consultarlo
- Idempotencia: **requerida por diseño**, con la misma garantía de unicidad que el envío manual
- Errores: SUNAT_NO_DISPONIBLE, SUNAT_RECHAZO, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
