# Contrato — Usuarios y acceso

Autoridad: Arquitectura. Interfaz interna del sistema (rutas web servidas por
Laravel/Livewire), no una API pública. Versión del contrato: v1.

## POST /login
- Ruta real: POST /login
- Path params: ninguno
- Query params: ninguno
- Request: `{ email: string (requerido), password: string (requerido) }`
- Response éxito: redirección a `/panel` con sesión iniciada
- Idempotencia: no aplica — autenticar dos veces no produce un efecto duplicado
- Concurrencia: no aplica
- Errores: CREDENCIALES_INVALIDAS, CAMPO_REQUERIDO, CAMPO_FORMATO_INVALIDO
- Autenticación: no requerida (actor `Anónimo`)

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| email (body) | string | requerido, no null, no vacío | formato de correo electrónico | máximo 150 caracteres | se recortan espacios de los extremos y se pasa a minúsculas antes de comparar | debe corresponder a un usuario con `activo = true` | CREDENCIALES_INVALIDAS | RF-001: presencia; derivado: formato y longitud del schema |
| password (body) | string | requerido, no null, no vacío | cualquier carácter, sin recorte ni transformación | mínimo 8, máximo 255 caracteres | ninguna — nunca se normaliza una contraseña | se compara contra el hash almacenado | CREDENCIALES_INVALIDAS | RF-001: presencia; derivado: longitud mínima propuesta |

Un usuario inactivo o inexistente devuelve exactamente el mismo error y en el
mismo tiempo que una contraseña incorrecta: no se revela cuál de los dos falló.

## POST /logout
- Ruta real: POST /logout
- Request: sin cuerpo
- Response éxito: redirección a `/login`, sesión invalidada
- Idempotencia: deduplicación — cerrar una sesión ya cerrada no es un error
- Errores: NO_AUTENTICADO
- Autenticación: requerida, roles `administrador` y `vendedor`

## GET /usuarios
- Ruta real: GET /usuarios
- Query params: `buscar?: string (opcional)`, `pagina?: entero (opcional, default 1)`
- Response éxito: listado paginado de 20 por página, ordenado por nombre y desempatado por `id`; nunca expone `password`
- Errores: NO_AUTENTICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

## POST /usuarios
- Ruta real: POST /usuarios
- Request: `{ nombre: string (requerido), email: string (requerido), password: string (requerido), rol: string (requerido) }`
- Response éxito: usuario creado, redirección al listado
- Idempotencia: deduplicación mediante la unicidad de `email`
- Errores: CAMPO_REQUERIDO, CAMPO_FORMATO_INVALIDO, CAMPO_FUERA_DE_RANGO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`

### Validaciones de entrada
| Campo/ubicación | Tipo semántico | Presencia/default | Formato/caracteres | Límites | Normalización/coerción | Regla cruzada/negocio | Error | Fuente/estado |
|---|---|---|---|---|---|---|---|---|
| nombre (body) | string | requerido, no null, no vacío ni solo espacios | letras, espacios, apóstrofos y guiones | 3 a 120 caracteres | recorte de espacios extremos, espacios internos colapsados | no aplica | CAMPO_REQUERIDO / CAMPO_FUERA_DE_RANGO | RF-002; derivado del schema |
| email (body) | string | requerido, no null, no vacío | formato de correo electrónico | máximo 150 caracteres | recorte y minúsculas | único entre todos los usuarios, activos e inactivos | CAMPO_FORMATO_INVALIDO / DOCUMENTO_DUPLICADO | RF-002; unicidad derivada del schema |
| password (body) | string | requerido, no null, no vacío | cualquier carácter | mínimo 8 caracteres | ninguna; se almacena solo el hash | no aplica | CAMPO_FUERA_DE_RANGO | derivado: mínimo propuesto |
| rol (body) | enum | requerido, no null | uno de `administrador`, `vendedor` | no aplica | ninguna | no aplica | CAMPO_FORMATO_INVALIDO | RF-002 |

## PATCH /usuarios/{id}
- Ruta real: PATCH /usuarios/{id}
- Path params: `id: entero`
- Request: cualquier subconjunto de `{ nombre, email, rol, activo }`; un campo ausente no cambia, `null` se rechaza
- Response éxito: usuario actualizado
- Concurrencia: no aplica — la edición de un usuario es infrecuente y el último cambio gana
- Errores: RECURSO_NO_ENCONTRADO, CAMPO_FORMATO_INVALIDO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO
- Autenticación: requerida, rol `administrador`
- Regla de negocio: un administrador no puede quitarse a sí mismo el rol de administrador ni desactivarse, para que el sistema nunca quede sin administrador activo. La contraseña no se cambia por esta ruta.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
