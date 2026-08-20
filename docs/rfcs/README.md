# RFCs por sprint

Un archivo por sprint, `S-<id>.md`. Cada uno deriva de los RF/RNF aprobados y fija qué
entrega ese sprint, con sus unidades de trabajo y sus criterios de cierre.

## Cómo leer la columna "Interfaz fijada"

**Las rutas que aparecen ahí —`GET /productos`, `POST /ventas`, `PATCH /clientes/{id}`—
NO son endpoints HTTP.** Identifican el recurso y la operación; no describen una ruta que
el enrutador atienda.

Los RFC se redactaron antes de [ADR-0006](../decisiones/0006-sin-api-http-interna.md), que
estableció que el sistema **no expone una API HTTP interna**: los componentes invocan los
servicios de dominio en el mismo proceso, y las únicas rutas HTTP que existen son las
transiciones de sesión (`POST /login`, `POST /logout`), las de infraestructura y las que
registra Livewire.

Así que, en todo RFC:

| Lo que dice la columna | Lo que significa hoy |
|---|---|
| `GET /recurso` en un sprint `-F` | La **pantalla** de ese recurso, servida desde `routes/web.php` |
| `GET /recurso` en un sprint `-B` | El **método del servicio de dominio** que sirve ese dato |
| `POST` / `PATCH` / `DELETE` | La **operación**, ejecutada por el servicio en el mismo proceso |

La correspondencia entre recurso y permiso sigue viviendo en
[`docs/requisitos/actores-permisos.md`](../requisitos/actores-permisos.md), que declara
quién puede hacer qué. Esa matriz **sigue vigente aunque las rutas no existan**: lo que
cambió es quién consume sus filas.

### Por qué esta nota existe en vez de trece enmiendas

Los trece RFC con interfaz declarada tienen la misma contradicción con ADR-0006. `qa` la
encontró al validar S-04-B: buscó las rutas del RFC, no existían, y **por un momento
pensó que faltaba media entrega**.

Enmendar cada RFC cerraría el estado de hoy y dejaría el mismo problema para el RFC número
catorce. Es la tercera vez que este proyecto elige declarar el principio en vez de
enumerar los casos — como los assets de Livewire por patrón, los permisos por área y el
propio ADR-0006.

**Lo que sigue siendo obligatorio enmendar en el RFC**, porque no lo cubre esta nota: un
cambio de alcance, una unidad que no se implementa, o un criterio de cierre que deja de
aplicar. Esta nota resuelve cómo se lee una columna, no qué entrega un sprint.
