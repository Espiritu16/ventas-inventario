---
project: ventas-inventario
source_status: CANONICA
baseline: documentación inicial aprobada 2026-08-19
active_phase: ola-2
active_status: LISTO
last_completed_phase: S-00
bootstrap_status: EN_PROGRESO
planning_horizon_status: COMPLETA
current_rfc_batch: []
planning_scope: [RF-001, RF-002, RF-003, RF-004, RF-005, RF-006, RF-007, RF-008, RF-009, RF-010, RF-011, RF-012, RF-013, RF-014, RF-015, RF-016, RF-017, RF-018, RF-019, RF-020, RF-021, RNF-001, RNF-002, RNF-003, RNF-004, RNF-005, RNF-006, RNF-007, RNF-008, RNF-010, RNF-011, RNF-012, RNF-013, RNF-014]
updated_at: 2026-08-19
repositories:
  - name: ventas-inventario
    path: ventas-inventario
    branch: main
    current_sha: null
sprints:
  - id: S-00
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-00
    base_sha: 99cd0618ec05f8386202813a2efa232724ec0bd8
    final_sha: ae2b0f73804c8b383dd970d91c1be379e305bc94
    merge_sha: 4e6af0e
    qa: APROBADO sobre f65efca con gobernanza c5c5389
    depends_on: []
    parallelizable_with: []
  - id: S-01-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: LISTO
    depends_on: [S-00]
    parallelizable_with: [S-DO-01]
  - id: S-02-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-01-B]
    parallelizable_with: [S-03-B, S-01-F]
  - id: S-03-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-01-B]
    parallelizable_with: [S-02-B, S-01-F]
  - id: S-04-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-02-B, S-03-B]
    parallelizable_with: [S-02-F]
  - id: S-05-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-B]
    parallelizable_with: [S-03-F]
  - id: S-06-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B]
    parallelizable_with: [S-07-B, S-08-B, S-04-F]
  - id: S-07-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B]
    parallelizable_with: [S-06-B, S-08-B, S-04-F]
  - id: S-08-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-B]
    parallelizable_with: [S-06-B, S-07-B]
  - id: S-01-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-01-B]
    parallelizable_with: [S-02-B, S-03-B]
  - id: S-02-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-02-B, S-03-B, S-01-F]
    parallelizable_with: [S-04-B]
  - id: S-03-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-B, S-01-F]
    parallelizable_with: [S-05-B]
  - id: S-04-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B, S-01-F]
    parallelizable_with: [S-06-B, S-07-B]
  - id: S-05-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-06-B, S-01-F]
    parallelizable_with: [S-06-F]
  - id: S-06-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-07-B, S-01-F]
    parallelizable_with: [S-05-F]
  - id: S-09-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-F]
    parallelizable_with: [S-05-F, S-06-F]
  - id: S-DO-01
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: LISTO
    depends_on: [S-00]
    parallelizable_with: [S-01-B]
  - id: S-QA-01
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-09-B, S-05-F, S-06-F]
    parallelizable_with: []
  - id: S-DO-02
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-QA-01, S-DO-01]
    parallelizable_with: []
---

# Estado del proyecto

## Progreso
- Documentación inicial completa y aprobada: gobernanza, RF, RNF, glosario, actores/permisos, contratos por dominio, modelo de persistencia con plan de migraciones, taxonomía de errores, experiencia e integración de la interfaz, integración con SUNAT y cinco ADR.
- Roadmap del horizonte aprobado: 19 sprints en 9 olas, con matriz de cobertura completa.
- Los 19 RFC redactados y aprobados por el usuario el 2026-08-19. Planificación del horizonte COMPLETA.
- Repositorio publicado en https://github.com/Espiritu16/ventas-inventario
- Ejecución iniciada el 2026-08-19. Los cinco chats de rol están abiertos y conectados por canal directo con el Coordinador.
- S-00 en curso en `sprint/S-00`, desde `main@99cd061`. UT-01, UT-03 y UT-04 verificadas; UT-02 y UT-05 quedaron detenidas por una precondición de entorno (rol y bases de PostgreSQL locales), resuelta por el usuario el mismo día y verificada por el Coordinador conectando con el rol de la aplicación contra `ventas_inventario` y `ventas_inventario_test`.

## Decisiones tomadas durante la ejecución

| # | Fecha | Qué se decidió | Quién lo aprobó | Dónde quedó |
|---|---|---|---|---|
| 1 | 2026-08-19 | Política de ramas de dos niveles: `sprint/<id>` → `develop` → `main`. Antes se integraba directo a la protegida | Kevin Espíritu | `AGENTS.md`, secciones "Política de ramas" y "CI por rama" |
| 2 | 2026-08-19 | La instalación de Livewire se ubica en S-01-B como UT-06. Ningún RFC del horizonte la declaraba, pese a que la gobernanza, ADR-0005 y los contratos la dan por existente | Kevin Espíritu | `docs/rfcs/S-01-B.md`, enmienda y UT-06 |

## S-00 — COMPLETADO (2026-08-19)

QA emitió **APROBADO** sobre `ventas-inventario@f65efca` con gobernanza
`develop@c5c5389`, tras un rechazo previo y su corrección. Fusionado a `develop` en
`4e6af0e`. Rama `sprint/S-00` conservada para auditoría.

Los cuatro comandos de verificación, las cifras y las versiones fueron reproducidos
por QA desde un checkout aislado, no aceptados del handoff.

Lo que este sprint deja, más allá del andamiaje: **dos mecanismos que nacieron de
fallos reales, encontrados por pruebas y no por revisión.**

1. Los instantes se escriben con su desplazamiento horario. La configuración de la
   conexión en UTC era necesaria pero no suficiente: Laravel enviaba las fechas sin
   zona y PostgreSQL las interpretaba como si ya fueran UTC, corriendo cada instante
   cinco horas. Nada fallaba; solo quedaba mal. QA lo confirmó por contrafáctico,
   revirtiendo el mecanismo y viendo reaparecer el corrimiento exacto. RNF-006 se
   cumple por mecanismo, no por una prueba que lo compense.
2. La suite aborta si la base a la que **efectivamente** se conectó no termina en
   `_test`, preguntándole el nombre al motor. La primera versión leía la
   configuración y se eludía por completo vía `DB_URL` — el caso que un CI o un
   contenedor producen de forma natural. Habría permitido destruir la base de
   aplicación con `migrate:fresh` y quedar en verde.

Observaciones informativas registradas por QA, ninguna accionable hoy:

- **OBS-A**: el guardián de arquitectura es una heurística por nombre y no ve
  `VentaRepositorioInterface` ni una clase sin sufijo como `CalculadoraDeIgv`. Es un
  límite inherente, no un defecto. No leerlo como garantía total.
- **OBS-B**: si `DB_CONNECTION` apuntara a un motor sin `current_database()`, la
  salvaguarda muere con error SQL en vez de su mensaje. Falla cerrado, que es lo
  correcto; solo el diagnóstico sería menos claro.
- **QA-05 confirmado empíricamente**: el tamaño del CSS depende del caché de vistas
  compiladas (`storage/framework/views`), no del código. Un build limpio da la cifra
  menor. Que nadie lo lea como regresión en S-DO-02.
- Sin verificar de forma independiente: el esqueleto `laravel/laravel v13.10.0`. El
  framework v13.26.1 sí. Es dato de proceso, no de producto.

**Riesgo residual para S-DO-01/S-DO-02, no para S-00**: `APP_DEBUG=true`,
`APP_ENV=local`, `SESSION_SECURE_COOKIE` sin definir y `SESSION_ENCRYPT=false` son
defaults del esqueleto. Deben endurecerse antes de que exista un entorno servido.

## Validación de S-00 — primer intento, RECHAZADO (2026-08-19)

QA validó `ventas-inventario@58a4c54` contra `develop@b746373`, en checkout aislado
fuera del árbol compartido, con `seguridad-validacion` activada en modo dirigido.
Veredicto **RECHAZADO** por un único defecto bloqueante. Las cinco unidades del RFC
cumplen su criterio literal y RNF-006 quedó confirmado por mecanismo: QA leyó el
instante desde el motor en cinco zonas horarias y comprobó por contrafáctico que,
al revertir la grammar, el corrimiento de cinco horas reaparece exacto.

| ID | Qué falla | Severidad | Propietario |
|---|---|---|---|
| QA-01 / SEG-01 | La salvaguarda de `tests/TestCase.php` se elude vía `DB_URL`: examina el campo de configuración, no el nombre efectivo que resuelve el driver. Con `DB_URL` presente valida un nombre y conecta a otro, así que `migrate:fresh` podría destruir la base de aplicación con la suite en verde. `phpunit.xml` intenta neutralizarlo pero PHPUnit no pisa una variable ya presente sin `force="true"` | **Bloqueante**, alta | `implementation-backend` |
| QA-02 | La prueba de migrate/rollback deja la base de pruebas desmantelada al terminar la suite | No bloqueante | `implementation-backend` |
| QA-03 | El detector de arquitectura de UT-04 reconoce los sufijos `Service`/`Repository` en inglés; con la nomenclatura española del proyecto no ve `VentaServicio` ni `ProductoRepositorio`. Queda ciego cuando S-01-B cree los primeros servicios | No bloqueante | `implementation-backend` |
| QA-04 | El README no menciona `pnpm build`; seguirlo al pie de la letra da 500 por falta de manifiesto de Vite | No bloqueante | Coordinación — **corregido** en esta misma enmienda |
| QA-05 | Diferencia de tamaño del CSS entre el handoff y un build limpio. No es defecto: `@source` sobre un caché no versionado del esqueleto | Informativo | — |

Riesgo residual anotado para S-DO-01/S-DO-02, no para S-00: `APP_DEBUG=true`,
`APP_ENV=local`, `SESSION_SECURE_COOKIE` sin definir y `SESSION_ENCRYPT=false` son
defaults del esqueleto y deben endurecerse antes de que exista un entorno servido.

Higiene de secretos verificada como buena: ningún `.env`, certificado ni clave
versionado en todo el rango del sprint; `composer audit` y `pnpm audit` sin
advisories.

## Aislamiento de base por carril — decidido antes de la ola 3

El usuario otorgó `CREATEDB` al rol `ventas_inventario` el 2026-08-19 (verificado:
`rolcreatedb = t`). Con eso, cada carril paralelo puede crear su propia base de
pruebas y la serialización por turnos deja de ser necesaria. Los turnos manuales
funcionaron con un carril; con tres no escalan.

**Convención de nombre — el orden de las partes no es estético:**

```
ventas_inventario_<carril>_test
```

Ejemplos: `ventas_inventario_s01f_test`, `ventas_inventario_s02b_test`,
`ventas_inventario_s03b_test`.

El sufijo `_test` va **al final, siempre**. La salvaguarda que S-00 dejó en
`tests/TestCase.php` aborta si el nombre efectivo de la base no termina en `_test`,
así que un nombre como `ventas_inventario_test_s02b` —que es el orden que sale
natural al escribirlo— sería rechazado por la propia protección y el carril no
podría correr sus pruebas. La protección funcionaría exactamente como debe; lo que
estaría mal es el nombre.

Reglas de uso:

- Cada carril fija su base en el `.env` de **su propio worktree**, que no se versiona.
  Ningún carril toca la base de otro.
- `ventas_inventario_test` queda como la base por defecto de quien trabaje sin
  paralelismo. No es de nadie en particular.
- `ventas_inventario` es la base de aplicación y ninguna suite la toca jamás. Esa es
  precisamente la garantía que la salvaguarda existe para dar.
- El carril crea su base al empezar y puede dejarla al terminar; no se exige
  limpiarla, porque `RefreshDatabase` la recompone y su nombre dice a qué sprint
  pertenece.

Esto no reemplaza el inventario de estado externo que hay que hacer en cada ola: la
base era un recurso compartido, no el único. Puertos, caché y directorios temporales
se siguen inventariando por ola, mirando la máquina y no razonando sobre ella.

## Ola 2 en curso — S-01-B rechazado en su primera validación (2026-08-19)

QA rechazó `b4fbc35` por dos defectos bloqueantes, ambos acotados y ninguno
estructural. El mecanismo de control de acceso quedó verificado a fondo: QA corrió
ocho mutaciones sobre él —quitar rutas de las listas, quitar el middleware global,
hacer que la autorización acepte siempre, abrir el patrón de assets a cualquier
método— y la suite detectó las ocho.

| ID | Qué falla | Severidad |
|---|---|---|
| QA-01 | Un administrador puede desactivarse a sí mismo enviando `activo: 0` o `"0"`. La guarda compara en estricto contra booleano y `validated()` devuelve el valor sin castear. Deja el sistema **sin administrador activo y sin forma de recuperarlo desde la aplicación**. `"0"` es lo que envía un checkbox de HTML, o sea el camino normal de la pantalla que construirá S-01-F | **Bloqueante**, alto |
| QA-02 / SEG-01 | El login distingue por tiempo un correo inexistente: 392 ms contra 191 ms, razón 2.05x, medible por red. La mitigación introdujo la señal que quería borrar, porque genera el hash señuelo dentro de la petición y ejecuta dos bcrypt en vez de uno. Permite enumerar usuarios | **Bloqueante**, medio |
| QA-03 | La lista de componentes accesibles sin sesión quedó vacía. **Error de secuencia mío**: fijé el nombre de la clase después de que Backend fijara su `final_sha` | No bloqueante |
| QA-04 | Un comentario del código repite el dato sobre `livewire.min.js` que ya se corrigió en la gobernanza | No bloqueante |

**SEG-02** — ver la enmienda de `docs/rfcs/S-08-B.md`.

Método a tener en cuenta en S-DO-02: la suite Feature exige que `pnpm build` haya
corrido antes, o cuatro pruebas fallan por falta del manifiesto de Vite. El orden de
los pasos del workflow importa.

## Entradas obligatorias para S-DO-02

Se registran acá, y no solo en el handoff de S-DO-01, porque son condiciones que
S-DO-02 debe cumplir y su RFC todavía no las declara.

- **Endurecimiento de configuración para el entorno servido**: `APP_ENV`,
  `APP_DEBUG`, `SESSION_SECURE_COOKIE` y `SESSION_ENCRYPT`. Lo levantó QA como riesgo
  residual al validar S-00. DevOps lo ubicó en S-DO-02 y no en S-DO-01, con este
  razonamiento, que acepto: en un entorno local de desarrollo `APP_DEBUG=true` y
  `APP_ENV=local` son lo correcto, no un defecto — forzarlos a `production` dentro
  del compose de desarrollo empeoraría el entorno sin proteger nada. `SESSION_SECURE_COOKIE`
  exige HTTPS y `SESSION_ENCRYPT` supone sesiones reales de usuarios; ninguna de las
  dos condiciones existe hoy. Lo que vuelve exigible el endurecimiento es
  precisamente el despliegue, que el RFC de S-DO-01 declara fuera de alcance.
- **No heredar la credencial local del compose.** La contraseña de la base
  contenerizada de S-DO-01 es un literal en `docker-compose.yml`, aceptable ahí
  porque el puerto no se publica y no da acceso a nada real. El compose de un entorno
  servido no puede heredar ese patrón.

## Pendientes de planificación

- ~~**Estado externo compartido en la ola 3.**~~ **RESUELTO el 2026-08-19** — ver "Aislamiento de base por carril" abajo. Lo detectó el chat de Frontend antes de que costara nada.
- **Árbol de trabajo único.** Las cinco sesiones comparten `/Users/sankef/ventas-inventario`. Hoy funciona porque S-00 corre solo, pero cualquier ola con dos sprints simultáneos exige worktrees dedicados por carril, acordados antes del despacho.

## Bloqueantes
- Ninguno para planificar ni para ejecutar. S-06-B se desarrolla y S-QA-01 valida contra el ambiente **beta**, con credenciales y certificado de prueba: no hacen falta datos del negocio.
- Condición futura, no bloqueante: el RUC real, la razón social, la dirección fiscal, el usuario SOL real y el certificado digital comprado se necesitan solo para el paso a producción, que exige autorización explícita del usuario. Ver `docs/integraciones/sunat.md`.

## Siguiente fase habilitada — ola 2

**S-01-B** (`implementation-backend`) y **S-DO-01** (`devops`) en paralelo, ambos
`LISTO`, ambos partiendo de `develop@<sha de cierre de S-00>`. Es el primer
paralelismo real del proyecto y el primer turno del chat de DevOps.

Inventario de estado externo compartido, hecho antes de despachar (el worktree
aísla archivos y ramas, no lo de afuera):

| Recurso | ¿Colisiona? | Decisión |
|---|---|---|
| Árbol de trabajo | Sí | **Separar**: un `git worktree` por carril, ninguno en `/Users/sankef/ventas-inventario` |
| Base `ventas_inventario_test` | No | Solo S-01-B la usa. S-DO-01 levanta su propio PostgreSQL en contenedor |
| Puerto 5432 | **Sí** | Lo ocupa el PostgreSQL del host. S-DO-01 **no publica** el suyo: los servicios del compose se hablan por su red interna. Además de evitar la colisión, vuelve imposible que el contenedor escriba por error en las bases del host |
| Puerto 8000 | **Sí** | Es el que el README documenta para `php artisan serve`, así que lo va a usar Backend al verificar S-01-B a mano. S-DO-01 publica su aplicación en **8080** y deja 8000 libre |
| Puerto 5173 | No | Libre, verificado |
| `vendor/`, `node_modules/` | No | Cada worktree instala lo suyo |

El conflicto del puerto 8000 lo detectó DevOps al verificar la máquina: mi inventario
inicial daba el 5432 como el único de la ola y era incorrecto. Un inventario de
estado externo no se completa razonando sobre la topología — se completa mirando qué
está ocupado.

No hace falta serializar en esta ola: solo un carril toca la base local. En la
**ola 3** eso deja de ser cierto — tres carriles simultáneos contra una sola base
de pruebas — y ahí sí hace falta el arreglo estructural, ver "Pendientes de
planificación".

Frontend sigue esperando: S-01-F depende de S-01-B, no de S-00. Los demás sprints
quedan en `PLANIFICADO` hasta que sus dependencias se completen.

## Referencias
- Roadmap: este documento, sección "Roadmap del horizonte"
- Prompts de apertura de los chats de rol: docs/chats-de-rol.md
- Contrato entre backend y frontend: docs/contratos/servicios-de-dominio.md
- Handoffs de sprint: docs/handoffs/
- Handoff activo: docs/handoffs/S-00.md, en la rama `sprint/S-00`
- Decisiones y contratos: docs/decisiones/, docs/contratos/, docs/persistencia/modelo.md

---

# Roadmap del horizonte

Cada sprint declara qué produce de forma observable, de qué documentos
aprobados nace, de qué depende y con qué puede correr en paralelo. El sufijo
`-B` marca sprints de backend, `-F` de frontend, `-DO` de DevOps y `-QA` de
validación.

El rol `implementation` está declarado en `AGENTS.md` en dos variantes con rutas
disjuntas, para que los dos frentes puedan trabajar a la vez sin escribir los
mismos archivos: `implementation-backend` (sufijo `-B`) e `implementation-frontend`
(sufijo `-F`). `S-00` es la excepción: funda el proyecto usando ambas rutas, así
que se ejecuta solo.

| ID | Rol | Resultado observable | Fuentes | Depende de | Paralelizable con | RFC |
|---|---|---|---|---|---|---|
| S-00 | implementation-backend | Proyecto Laravel fundado y corriendo en local, con PostgreSQL, Tailwind vía Vite, la estructura `app/Dominios/`, y los comandos de lint, pruebas y build funcionando de verdad | ADR-0001, ADR-0005, AGENTS.md | — | — | docs/rfcs/S-00.md |
| S-DO-01 | devops | Entorno reproducible con Docker Compose: aplicación, PostgreSQL y proceso trabajador de cola, levantables con un comando | ADR-0001, ADR-0003, RNF-002 | S-00 | S-01-B | docs/rfcs/S-DO-01.md |
| S-01-B | implementation-backend | Inicio y cierre de sesión, alta y edición de usuarios, y control de acceso por rol aplicado en el servidor sobre cada ruta | RF-001, RF-002, MIG-001, actores-permisos, contratos/usuarios | S-00 | S-DO-01 | docs/rfcs/S-01-B.md |
| S-02-B | implementation-backend | Categorías y productos con precio menor y mayor, stock mínimo y su validación de precios | RF-003, RF-004, MIG-002, contratos/productos | S-01-B | S-03-B, S-01-F | docs/rfcs/S-02-B.md |
| S-03-B | implementation-backend | Proveedores y clientes con validación de documento según su tipo | RF-005, RF-010, MIG-003, MIG-006, contratos/proveedores, contratos/clientes | S-01-B | S-02-B, S-01-F | docs/rfcs/S-03-B.md |
| S-04-B | implementation-backend | Compras que crean lotes con vencimiento y costo, consulta de stock por lote, kardex inmutable y ajustes con motivo | RF-006, RF-007, RF-008, RF-009, MIG-004, MIG-005, ADR-0004, contratos/compras, contratos/inventario | S-02-B, S-03-B | S-02-F | docs/rfcs/S-04-B.md |
| S-05-B | implementation-backend | Venta registrada en una transacción: descuento FEFO con reparto por lote, cálculo de IGV, reserva de correlativo y comprobante en estado pendiente | RF-011, RF-012, RF-013, RF-014, MIG-007, MIG-008, RNF-003, contratos/ventas | S-04-B | S-03-F | docs/rfcs/S-05-B.md |
| S-06-B | implementation-backend | Emisión electrónica real contra el ambiente beta de SUNAT: XML firmado, envío en segundo plano con reintentos, constancia CDR guardada, reenvío manual y resumen diario de boletas | RF-015, RF-016, RF-017, ADR-0002, ADR-0003, integraciones/sunat, contratos/comprobantes | S-05-B | S-07-B, S-08-B, S-04-F | docs/rfcs/S-06-B.md |
| S-07-B | implementation-backend | Consultas de alertas de vencimiento y stock bajo, y reportes de ventas y de utilidad con costo real por lote | RF-018, RF-019, RF-020, RF-021, contratos/inventario, contratos/ventas | S-05-B | S-06-B, S-08-B, S-04-F | docs/rfcs/S-07-B.md |
| S-08-B | implementation-backend | Política completa de auditoría aplicada a cada operación sensible, y registro de errores con saneamiento de secretos y canal independiente | RNF-004, RNF-014, modelo (Auditoria, LogError) | S-04-B | S-06-B, S-07-B | docs/rfcs/S-08-B.md |
| S-01-F | implementation-frontend | Base de la interfaz: layout, menú por rol, componentes reutilizables y pantalla de inicio de sesión | RF-001, RF-002, frontend/experiencia, frontend/integracion | S-01-B | S-02-B, S-03-B | docs/rfcs/S-01-F.md |
| S-02-F | implementation-frontend | Pantallas de catálogo, proveedores, clientes y usuarios, con sus validaciones en el momento de escribir | RF-002, RF-003, RF-004, RF-005, RF-010, frontend/experiencia | S-02-B, S-03-B, S-01-F | S-04-B | docs/rfcs/S-02-F.md |
| S-03-F | implementation-frontend | Pantallas de compra, consulta de inventario por lote, kardex y ajuste | RF-006, RF-007, RF-008, RF-009, frontend/experiencia | S-04-B, S-01-F | S-05-B | docs/rfcs/S-03-F.md |
| S-04-F | implementation-frontend | Pantalla de caja completa: búsqueda por teclado y código de barras, precio menor o mayor por línea, confirmación e impresión del comprobante | RF-011, RF-012, RF-013, RNF-008, frontend/experiencia | S-05-B, S-01-F | S-06-B, S-07-B | docs/rfcs/S-04-F.md |
| S-05-F | implementation-frontend | Pantalla de seguimiento de comprobantes con reenvío, y pantalla de resúmenes diarios | RF-016, RF-017, frontend/experiencia | S-06-B, S-01-F | S-06-F | docs/rfcs/S-05-F.md |
| S-06-F | implementation-frontend | Tablero de alertas de vencimiento y stock bajo, y pantallas de reportes de ventas y utilidad | RF-018, RF-019, RF-020, RF-021, frontend/experiencia | S-07-B, S-01-F | S-05-F | docs/rfcs/S-06-F.md |
| S-09-B | implementation-backend | Pruebas de extremo a extremo del recorrido de venta, y prueba de rendimiento que mide el umbral de confirmación de venta sobre volumen realista | RNF-001, RNF-008, AGENTS.md (comando E2E) | S-04-F | S-05-F, S-06-F | docs/rfcs/S-09-B.md |
| S-QA-01 | qa | Veredicto de validación integral: funcional sobre todo el horizonte, concurrencia, seguridad de aplicación y emisión contra el ambiente beta de SUNAT | RNF-001 a RNF-014, todos los RF | S-09-B, S-05-F, S-06-F | — | docs/rfcs/S-QA-01.md |
| S-DO-02 | devops | Despliegue del sistema en el servidor, con respaldo diario probado, verificación de salud, recolección de logs y procedimiento de reversión | RNF-002, RNF-007, AGENTS.md (operación DevOps) | S-QA-01, S-DO-01 | — | docs/rfcs/S-DO-02.md |

## Matriz de cobertura

| Fuente | Sprint que la cubre |
|---|---|
| RF-001, RF-002 | S-01-B (servidor), S-01-F (pantalla) |
| RF-003, RF-004 | S-02-B, S-02-F |
| RF-005, RF-010 | S-03-B, S-02-F |
| RF-006, RF-007, RF-008, RF-009 | S-04-B, S-03-F |
| RF-011, RF-012, RF-013, RF-014 | S-05-B, S-04-F |
| RF-015, RF-016, RF-017 | S-06-B, S-05-F |
| RF-018, RF-019, RF-020, RF-021 | S-07-B, S-06-F |
| RNF-001 (rendimiento) | S-09-B mide, S-QA-01 valida |
| RNF-002 (disponibilidad ante SUNAT) | S-06-B implementa, S-QA-01 valida |
| RNF-003 (integridad y concurrencia) | S-04-B y S-05-B implementan, S-QA-01 valida |
| RNF-004 (trazabilidad) | S-01-B crea las tablas, cada sprint audita lo suyo, S-08-B cierra la política completa |
| RNF-005 (custodia del certificado) | S-06-B implementa la alerta, S-DO-02 la custodia en el servidor |
| RNF-006 (fecha, hora y moneda) | S-00 configura, cada sprint de dominio lo respeta, S-QA-01 valida |
| RNF-007 (respaldo) | S-DO-02 |
| RNF-008 (operación con teclado) | S-04-F implementa, S-09-B automatiza, S-QA-01 valida |
| RNF-010 a RNF-014 (seguridad) | cada sprint las aplica; S-QA-01 las valida con `seguridad-validacion` |
| MIG-001 a MIG-009 | S-01-B (MIG-001 y MIG-009), S-02-B (MIG-002), S-03-B (MIG-003, MIG-006), S-04-B (MIG-004, MIG-005), S-05-B (MIG-007, MIG-008) |
| Integración SUNAT | S-06-B |
| AGENTS.md — lint, pruebas, build, gestor de paquetes (`previsto en S-00`) | S-00 |
| AGENTS.md — comando E2E (hoy `no aplica`) | S-09-B lo define y lo vuelve real |
| AGENTS.md — pipeline, artefacto, health/smoke, observabilidad, reversión (`previsto al definir el despliegue`) | S-DO-02 |
| AGENTS.md — navegadores y viewports de QA | S-01-F los fija junto con la base de interfaz; S-QA-01 los usa |

## Olas de ejecución

1. **Ola 1** — S-00. Nada más puede empezar antes.
2. **Ola 2** — S-01-B y S-DO-01 en paralelo.
3. **Ola 3** — S-02-B, S-03-B y S-01-F en paralelo.
4. **Ola 4** — S-04-B y S-02-F en paralelo.
5. **Ola 5** — S-05-B y S-03-F en paralelo.
6. **Ola 6** — S-06-B, S-07-B, S-08-B y S-04-F en paralelo.
7. **Ola 7** — S-05-F, S-06-F y S-09-B en paralelo.
8. **Ola 8** — S-QA-01.
9. **Ola 9** — S-DO-02.

---

# Avisos al usuario — obligación del Coordinador

Hay puntos del proyecto que **no se pueden resolver sin el dueño del negocio**.
El Coordinador es responsable de avisarle **antes** de que el sprint se bloquee,
no cuando ya está detenido. Si un sprint llega a su turno y falta uno de estos
datos, se marca `BLOQUEADO` y el motivo se reporta explícitamente al usuario.

Ningún rol asume, inventa ni deja "para después" uno de estos puntos, y ninguno
se salta el aviso porque "seguro lo tiene". El aviso se da aunque el Coordinador
crea que el usuario ya lo sabe.

| Cuándo avisar | Qué se necesita del usuario | Qué se bloquea si falta |
|---|---|---|
| Al cerrar **S-05-B**, antes de habilitar S-06-B | RUC y razón social del emisor, dirección fiscal, usuario secundario SOL y su clave, y el **certificado digital de pruebas**. Todo esto es para el ambiente **beta**: son datos de prueba, no el certificado real de producción | S-06-B completo, y con él S-05-F. El resto del proyecto sigue avanzando |
| Al cerrar **S-QA-01**, antes de habilitar S-DO-02 | Decisión del proveedor y servidor donde vivirá el sistema, con PostgreSQL disponible. Si el proveedor elegido no lo ofrece, hay que reabrir el ADR-0001 antes de continuar | S-DO-02 completo |
| Dentro de **S-DO-02**, antes de cada operación | Autorización explícita para desplegar, para instalar el **certificado digital real**, y para cambiar el ambiente de SUNAT de `beta` a `produccion`. Se pide inmediatamente antes de cada acción, nunca por adelantado ni en bloque | La operación puntual, no el sprint |
| Antes de cualquier `git push` que no esté pre-autorizado | Confirmación puntual en el chat | El push |
| Cuando QA emite **RECHAZADO** o **BLOQUEADO** | Que el usuario sepa qué falló y decida si se corrige, se posterga o se acepta el riesgo | El cierre del sprint |
| Cuando un cambio invalida un documento aprobado | Reaprobación del documento afectado (RFC, contrato, `AGENTS.md`) | Los sprints que dependan de ese documento |
| Cuando aparezca una decisión que cambie alcance, costo o riesgo | Su decisión, con las alternativas explicadas antes de preguntar | Lo que dependa de esa decisión |

**Sobre el aviso de SUNAT en particular**, que es el más previsible: el Coordinador
lo anuncia al cerrar S-05-B, no al empezar S-06-B. Conseguir un certificado de
pruebas y un usuario SOL toma tiempo, y avisar el mismo día en que hace falta
significa detener el proyecto por trámite.
