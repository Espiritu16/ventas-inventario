# Contrato — Proveedores

> **Gobernado por [ADR-0006](../decisiones/0006-sin-api-http-interna.md)**: los recursos de dominio se sirven como **pantallas**, no como endpoints HTTP. Las rutas que este documento describe son las de esas pantallas; las filas cuyo verbo no es `GET` describen **operaciones** que el componente ejecuta invocando el servicio en el mismo proceso, no rutas que el enrutador atienda. Agregado el 2026-08-19, tras encontrar el mismo choque replicado en cuatro dominios.


Autoridad: Arquitectura. Versión del contrato: v1.

## GET /proveedores
- Query params: `buscar?: string (opcional — razón social o número de documento)`, `soloActivos?: boolean (opcional, default true)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por razón social y desempatado por `id`
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## POST /proveedores
- Request: `{ numeroDocumento: string (requerido), razonSocial: string (requerido), direccion?: string, telefono?: string, email?: string }`
- Idempotencia: deduplicación mediante la unicidad de tipo y número de documento
- Errores: CAMPO_REQUERIDO, DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| numeroDocumento (body) | string | requerido, no vacío | exactamente 11 dígitos, sin espacios ni guiones | 11 caracteres | recorte de extremos; se rechaza si trae separadores, no se limpian en silencio | único; el tipo de documento es siempre RUC (`6`) para un proveedor | DOCUMENTO_INVALIDO / DOCUMENTO_DUPLICADO | RF-005 |
| razonSocial (body) | string | requerido, no vacío ni solo espacios | texto libre | 3 a 200 caracteres | recorte, espacios internos colapsados | no aplica | CAMPO_REQUERIDO | RF-005 |
| direccion (body) | string | opcional, default null | texto libre | máximo 255 caracteres | recorte | no aplica | CAMPO_FUERA_DE_RANGO | RF-005 |
| telefono (body) | string | opcional, default null | dígitos, espacios, `+` y guiones; sin letras | 6 a 20 caracteres | recorte | no aplica | CAMPO_FORMATO_INVALIDO | RF-005; formato propuesto |
| email (body) | string | opcional, default null | formato de correo electrónico | máximo 150 caracteres | recorte y minúsculas | no aplica | CAMPO_FORMATO_INVALIDO | RF-005 |

## PATCH /proveedores/{id}
- Request: subconjunto de `{ razonSocial, direccion, telefono, email, activo }`. El número de documento no se modifica: identifica al proveedor en compras ya registradas.
- Errores: RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
