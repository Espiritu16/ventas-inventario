# Contrato — Clientes

> **Gobernado por [ADR-0006](../decisiones/0006-sin-api-http-interna.md)**: los recursos de dominio se sirven como **pantallas**, no como endpoints HTTP. Las rutas que este documento describe son las de esas pantallas; las filas cuyo verbo no es `GET` describen **operaciones** que el componente ejecuta invocando el servicio en el mismo proceso, no rutas que el enrutador atienda. Agregado el 2026-08-19, tras encontrar el mismo choque replicado en cuatro dominios.


Autoridad: Arquitectura. Versión del contrato: v1.

## GET /clientes
- Query params: `buscar?: string (opcional — nombre o número de documento)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por nombre y desempatado por `id`
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`

## POST /clientes
- Ruta real: POST /clientes
- Request: `{ tipoDocumento: string (requerido), numeroDocumento?: string (condicional), nombre: string (requerido), direccion?: string, telefono?: string, email?: string }`
- Response éxito: cliente creado. Se puede invocar desde la propia pantalla de venta sin abandonarla (RF-010)
- Idempotencia: deduplicación mediante el índice único parcial de tipo y número de documento. **Precisado el 2026-08-19**: la unicidad es por la combinación, así que un DNI y un carné con los mismos dígitos conviven — son documentos distintos de personas potencialmente distintas. Es lo correcto para el dominio, y antes había que deducirlo del índice. Lo señaló `qa` al validar S-03-B
- Errores: CAMPO_REQUERIDO, DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, roles `administrador` y `vendedor`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| tipoDocumento (body) | enum | requerido, no null | uno de `0` (sin documento), `1` (DNI), `4` (carné de extranjería), `6` (RUC) | no aplica | ninguna | una factura exige `6`; ver contrato de ventas | CAMPO_FORMATO_INVALIDO | RF-010; catálogo de SUNAT |
| numeroDocumento (body) | string | requerido salvo cuando `tipoDocumento = 0`, donde debe ser null | solo dígitos para DNI y RUC; alfanumérico para carné de extranjería | DNI exactamente 8; RUC exactamente 11; carné hasta 12 | recorte de extremos; se rechazan separadores, no se limpian | longitud según el tipo; **único por la combinación de tipo y número**, no por el número solo, y únicamente cuando está presente | CAMPO_REQUERIDO si falta / DOCUMENTO_INVALIDO si el formato o la longitud fallan / DOCUMENTO_DUPLICADO solo si esa combinación ya existe. **Cuando un número es a la vez inválido y repetido se informa el formato**, porque de nada sirve decir que está repetido si está mal escrito | RF-010 |
| nombre (body) | string | requerido, no vacío ni solo espacios | texto libre | 3 a 200 caracteres | recorte, espacios internos colapsados | para `tipoDocumento = 0` se admite el valor por defecto "público general" | CAMPO_REQUERIDO | RF-010 |
| direccion (body) | string | opcional salvo para factura, donde es requerida | texto libre | máximo 255 caracteres | recorte | una factura exige dirección, porque viaja en el comprobante | CAMPO_REQUERIDO | RF-010 más docs/integraciones/sunat.md |
| telefono (body) | string | opcional, default null | dígitos, espacios, `+` y guiones | 6 a 20 caracteres | recorte | no aplica | CAMPO_FORMATO_INVALIDO | RF-010; formato propuesto |
| email (body) | string | opcional, default null | formato de correo electrónico | máximo 150 caracteres | recorte y minúsculas | no aplica | CAMPO_FORMATO_INVALIDO | RF-010 |

## PATCH /clientes/{id}
- Request: subconjunto de `{ nombre, direccion, telefono, email, activo }`. Tipo y número de documento no se modifican: identifican al cliente en comprobantes ya emitidos.
- Errores: RECURSO_NO_ENCONTRADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
