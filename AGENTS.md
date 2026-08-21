# AGENTS.md — ventas-inventario

> Define permisos, ramas y convenciones locales que project-continuity debe
> respetar. No redefine el proceso general de trabajo (vive en la skill);
> solo declara lo específico de este repositorio.

## Proyecto
- Proyecto: Sistema de ventas e inventario para comercializadora
- Identificador canónico del repositorio: ventas-inventario
- Tipo de repositorio: fullstack (backend Laravel + UI Blade/Livewire en el mismo repositorio)
- Stack/framework: PHP + Laravel + Livewire + Tailwind CSS; Greenter para emisión electrónica SUNAT — fuente: decisión del usuario en la sesión de diseño (2026-08-19)
- Runtime y versión: PHP 8.5.9 y Laravel v13.26.1 (esqueleto `laravel/laravel` v13.10.0) — verificados al fundar en S-00 contra la fuente oficial y `composer.lock`, no recordados
- Gestor de paquetes/build: Composer 2.10.2 (PHP) y pnpm 11.22.0 sobre Node 24.19.0 para los assets vía Vite 8.2.1 — verificados al fundar en S-00
- Persistencia/motor: PostgreSQL 18.3, verificado al fundar en S-00 — decidido en ADR-0001
- Estado global / roadmap: docs/estado-global.md
- Handoffs de sprint: docs/handoffs/<id-sprint>.md — el primero se crea al cerrar S-00; lo produce el proceso de cierre de sprint, no una unidad de trabajo del RFC

## Vigencia de gobernanza
- Estado de gobernanza: APROBADO
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — reaprobado el 2026-08-20 al fusionar el PR #2, que declara los permisos por área en vez de por lista de rutas
- Fecha de la reaprobación: 2026-08-20
- Fecha de aprobación: 2026-08-19

## Roles activos en este repositorio

### coordinacion
- Puede escribir gobernanza y planificación: `AGENTS.md`, `docs/estado-global.md`, `docs/rfcs/`, `docs/decisiones/`, `docs/despacho-de-roles.md`
- Puede escribir en `docs/handoffs/`: las secciones de resultados QA/DevOps y el campo `status` de la cabecera al cerrar el sprint. El resto del handoff es del rol que lo ejecutó — cerrar un sprint es atribución del Coordinador, así que registrar ese cierre también lo es
- Puede redactar borradores documentales dentro de: `docs/requisitos/`, `docs/frontend/experiencia.md`, `docs/integraciones/`, `README.md`
- No puede: implementar código, autoaprobar al usuario, sustituir la aprobación de Arquitectura, emitir el veredicto QA ni ejecutar trabajo DevOps

### implementation

Este repositorio es fullstack y sus sprints se reparten en dos frentes que trabajan
en paralelo (ver ADR-0005). Por eso el rol `implementation` se declara en **dos
variantes con rutas disjuntas**: son el mismo rol de `project-continuity`, con
alcance acotado para que dos sesiones simultáneas no escriban los mismos archivos.
Un sprint declara cuál variante le corresponde según su sufijo: `-B` backend, `-F`
frontend. `S-00` es la única excepción: funda el proyecto y usa ambas rutas, por lo
que se ejecuta solo, sin nada en paralelo.

#### implementation-backend (sprints con sufijo `-B`, y `S-00`)
- Puede escribir código y pruebas: `app/Dominios/*/` **excepto** la subcarpeta `Livewire/` de cada dominio, `app/Compartido/`, `app/Http/Middleware/`, `app/Providers/`, `app/Jobs/`, `app/Console/`, `database/`, `config/`, y **todo `tests/` excepto `tests/Feature/Livewire/`** — declarado por área y no por lista, para que un directorio de pruebas nuevo no quede sin dueño (ver "Permisos por área" abajo)
- Puede escribir bootstrap/configuración cuando el RFC lo autoriza: `composer.json`, `composer.lock`, `.env.example` (sin secretos), `database/migrations/`, y el andamiaje que el framework exige y ningún otro rol cubre: `bootstrap/`, **todo `public/`**, `artisan`, `phpunit.xml`, `pint.json`, `routes/console.php`, y los archivos de configuración de la raíz que ningún otro rol cubre (`.gitignore`, `.editorconfig`, `.gitattributes`, `.npmrc`)
- Es el dueño de las firmas declaradas en `docs/contratos/servicios-de-dominio.md`: puede proponer cambios, pero la aprobación es de Arquitectura
- Puede escribir las rutas HTTP del servidor en `routes/backend.php`, registrado desde `bootstrap/app.php`. `routes/web.php` sigue siendo exclusivo de `implementation-frontend`: los dos frentes nunca escriben el mismo archivo de rutas, que es lo que permite que trabajen a la vez. El control de acceso deny-by-default de RNF-013 se aplica por igual a los dos grupos de rutas; una ruta sin declaración en `docs/requisitos/actores-permisos.md` se rechaza, venga del archivo que venga
- No puede escribir: `resources/views/`, `resources/css/`, `resources/js/`, `routes/web.php`, ni ninguna subcarpeta `Livewire/` — **salvo en S-00**, donde crea el andamiaje inicial de esas rutas (entry points de Vite, layout base vacío y `routes/web.php` con la ruta raíz), tal como declara la excepción del encabezado de este rol. A partir de S-01-F, esas rutas pasan a ser exclusivas de `implementation-frontend`

#### implementation-frontend (sprints con sufijo `-F`)
- Puede escribir código y pruebas: `app/Dominios/*/Livewire/`, `resources/views/`, `resources/css/`, `resources/js/`, `routes/web.php`, `tests/Feature/Livewire/`, `app/Compartido/Interfaz/`
- Puede escribir bootstrap/configuración cuando el RFC lo autoriza: `package.json`, `pnpm-lock.yaml`, `vite.config.js`, `tailwind.config.js`
- Consume las firmas de `docs/contratos/servicios-de-dominio.md`; **no las cambia**. Si necesita una firma distinta, escala a Arquitectura y el sprint queda bloqueado hasta que se apruebe
- No puede escribir: `app/Dominios/*/` fuera de `Livewire/`, `database/`, `config/`, `app/Http/Middleware/`

#### Reglas comunes a ambas variantes
- Puede actualizar únicamente este handoff: `docs/handoffs/<id-de-su-propio-sprint>.md`; nunca el estado global ni handoffs ajenos
- No puede modificar sin autorización de Arquitectura: `docs/contratos/`, `docs/persistencia/`, `docs/errores/`, `docs/requisitos/actores-permisos.md`, `docs/frontend/integracion.md`, `docs/decisiones/`
- Si un sprint necesita tocar una ruta de la otra variante, **no la toca**: reporta el desajuste al Coordinador, que decide si corresponde reabrir el RFC o coordinar con el otro frente. Descubrir un archivo compartido no declarado significa que la línea base era incorrecta, no que se pueda escribir igual

### qa
- Puede leer: todo el repositorio
- Debe validar con la skill `qa-validacion`, usando el RFC y los `final_sha` exactos
- Debe evaluar y activar automáticamente `seguridad-validacion` según superficie/riesgo; no requiere un rol `security` separado por defecto
- **Debe comprobar que la suite detecta la ausencia del mecanismo** en sprints que tocan autorización, persistencia o configuración: rompe a mano una línea del control y verifica que alguna prueba falla. Sobre árbol limpio, confirmando que la mutación se aplicó, y restaurando después. Ver `docs/estado-global.md`, "Práctica adoptada"
- Puede ejecutar: los comandos de verificación declarados abajo y crear artefactos desechables fuera del árbol objetivo
- No puede escribir ni commitear durante la validación. Automatización o fixtures versionados requieren un sprint separado bajo rol `implementation`; QA puede definir los casos
- No puede: hacer merge, modificar implementación para pasar pruebas

### arquitectura
- Única autoridad sobre: contratos/interfaces, schema de persistencia, ADR, manejo de errores (formato y taxonomía), actores/permisos sobre cada ruta web, comando, job y cola, e integración frontend técnica
- Puede escribir/proponer dentro de: `docs/contratos/`, `docs/persistencia/`, `docs/errores/`, `docs/requisitos/actores-permisos.md`, `docs/frontend/integracion.md`, `docs/integraciones/sunat.md` (parte técnica), `docs/decisiones/`
- No puede: implementar código por ejercer Arquitectura, autoaprobar decisiones reservadas al usuario, sustituir QA ni cerrar sprints

### devops
- Debe usar la skill `devops-entrega` y reportar sobre `repositorio@final_sha` + artefacto/digest exactos
- Puede escribir: `Dockerfile`, `docker-compose.yml`, `.dockerignore`, **todo `docker/`**, `.github/workflows/`, scripts de build/deploy, configuración operativa
- Ejecución delegada al agente `devops-engineer` cuando esté disponible
- Puede operar sin nueva aprobación solo: entorno local de desarrollo
- No puede: desplegar a producción, alterar infraestructura externa real, rotar secretos, enviar comprobantes al ambiente de producción de SUNAT, ni ejecutar operaciones destructivas sin autorización explícita del usuario

## Política de ramas
- protegida: `main`
- integración: `develop` — nace de `main`; es la rama de la que todo rol parte y contra la que se integra
- trabajo: `sprint/<id>`, `feature/<nombre>`, `fix/<nombre>`; gobernanza: `gobernanza/<tema>`
- Remoto: `origin` → https://github.com/Espiritu16/ventas-inventario (público)
- Entrega de Implementación: pull request desde la rama de trabajo hacia `develop`, con el `final_sha` y el handoff referenciados en su descripción. Ningún pull request de sprint apunta a `main`
- Gate antes de integrar a `develop`: QA APROBADO sobre ese `final_sha` cuando el sprint requiere QA; solo el Coordinador integra y cierra
- Promoción `develop` → `main`: paso separado y explícito, en lote, nunca automático por sprint. Exige que los checks obligatorios de `main` pasen
- Ramas de gobernanza: las integra el Coordinador directo a `develop`, sin esperar un sprint; son el mecanismo por el que una enmienda de `AGENTS.md` se vuelve visible antes de despachar
- Despacho con dos anclas: todo despacho de implementación o validación indica el `final_sha` del código **y** `gobierna: develop@<sha>`, el commit donde vive la gobernanza vigente. Una rama de sprint creada antes de una enmienda lleva el `AGENTS.md` viejo en su árbol; el segundo ancla es lo que evita que quien valide derive la política obsoleta

## CI por rama
- `develop`: previsto en S-DO-02: workflow de GitHub Actions que corre lint, pruebas unitarias, pruebas de integración y build en cada pull request hacia esta rama.
- `main`: previsto en S-DO-02: los mismos checks, obligatorios antes de promover `develop` a `main`.
- Hasta que ese sprint se ejecute, la verificación es local y obligatoria antes de abrir el pull request: los comandos declarados abajo deben pasar y su resultado se registra en el handoff.

## Convención de commits
- Formato: `<tipo>(<alcance>): <descripción corta>`
  tipos válidos: feat, fix, chore, docs, test, refactor, style, perf
- Descripción y cuerpo en español, en pasado impersonal ("se agregó", "se corrigió"); el prefijo de tipo va en inglés por ser estándar
- Referenciar el sprint en el cuerpo: `Sprint: <id-sprint>`
- Un commit = un cambio lógico coherente
- Sin emojis salvo pedido explícito; sin línea `Co-Authored-By`
- Prohibido: commit directo a `main`; todo cambio de implementación pasa por pull request
- Prohibido: `--force` push a `main` sin autorización explícita

## Comandos de verificación
- Lint: `./vendor/bin/pint --test` — real y en verde desde S-00
- Tests unitarios/componentes: `php artisan test --testsuite=Unit` — real y en verde desde S-00
- Integración/contrato: `php artisan test --testsuite=Feature` — real y en verde desde S-00. **La base de pruebas se deriva por árbol de trabajo** desde el 2026-08-21 (`tests/EntornoDePruebas.php`, invocado por `tests/bootstrap.php`): `phpunit.xml` ya no declara `DB_DATABASE`, así que dos worktrees no comparten base y no se destruyen entre sí. Exportar `DB_DATABASE` en el entorno sigue mandando sobre la derivación. Hay **dos guardas**: la del bootstrap valida el nombre antes de que exista conexión, y la de `tests/TestCase.php` **aborta antes de tocar nada** si la base a la que efectivamente se conectó no respeta la convención `ventas_inventario_<carril>_test`, y lo determina preguntándole el nombre al motor (`select current_database()`), no leyendo la configuración. Eso importa: el campo de configuración y la conexión real pueden divergir, por ejemplo cuando `DB_URL` pisa los campos sueltos, que es como un CI o un contenedor suelen inyectar la conexión. Verificado por QA en los tres sentidos durante S-00 — base correcta, nombre fuera de `_test`, y `DB_URL` divergente. El mensaje de aborto nombra la base efectiva y la declarada, para que una divergencia futura se diagnostique sin investigar
- E2E: no aplica — sin herramienta E2E decidida; se reevalúa cuando exista la pantalla de caja
- Accesibilidad: previsto en S-09-B: recorrido completo de la venta operable solo con teclado, verificado de forma automatizada (RNF-008)
- Build: `pnpm build` — real y en verde desde S-00
- Otros RNF: previsto en S-06-B: envío de comprobante contra el ambiente **beta** de SUNAT con verificación de CDR. Rendimiento (RNF-001) y operación con teclado (RNF-008): previsto en S-09-B

## Validación QA
- Entorno autorizado: local, con base de datos de pruebas dedicada
- Identidades/datos de prueba: seeders del repositorio y el RUC/certificado de **pruebas** de SUNAT; nunca el certificado digital real ni secretos
- Evidencia durable: `docs/handoffs/<id-sprint>.md` bajo responsabilidad del Coordinador
- Retención de artefactos externos: no aplica — sin CI ni almacenamiento externo
- Navegadores/viewports requeridos: **motor Blink** —Chrome y Edge lo comparten desde 2020, y las diferencias entre ellos están en integración con el sistema operativo, no en layout ni en teclado—, viewport mínimo 1366x768. Se valida en un navegador Chromium y **se declara cuál**, incluido si no es Chrome de escritorio. La caja no se opera desde móvil (RNF-008: operación con teclado y lector de código de barras)
- Seguridad de aplicación: automática por superficie/riesgo mediante `seguridad-validacion`
- Motores permitidos: auto según plataforma; nunca asumir disponibilidad
- Alcance dinámico autorizado: local y ambiente **beta** de SUNAT. Producción de SUNAT y cualquier envío con el certificado real: prohibido sin autorización explícita del usuario inmediatamente antes
- Gate especializado: no aplica todavía; un reporte de motor no sustituye el veredicto QA

## Operación DevOps/Release
- Proveedor/topología: no decidido — servidor en internet, proveedor pendiente de ADR
- Ambientes: local (autorizado), producción (no autorizado sin aprobación explícita por operación)
- Pipeline: no decidido
- Artefacto canónico: no aplica — sin empaquetado decidido
- Configuración/secretos: variables de entorno declaradas en `.env.example` por nombre; el certificado digital tributario y su clave nunca se versionan ni se registran en documentación
- Migraciones: `php artisan migrate`, ejecutadas por el rol implementation en local y por DevOps en despliegue; nunca automáticas sobre datos reales sin autorización
- Health/smoke: previsto al definir el despliegue
- Observabilidad: previsto al definir el despliegue; mínimo exigido — registro de cada envío a SUNAT con su resultado
- Rollback/roll-forward: previsto al definir el despliegue
- Evidencia durable: `docs/handoffs/`
- Retención: no aplica todavía
- Producción: requiere autorización explícita del usuario inmediatamente antes de mutar

## Autoridad de contratos

Repositorio único: `ventas-inventario` es dueño de todos sus contratos y su
Arquitectura los aprueba. No hay contratos compartidos con otro repositorio.

El contrato que backend y frontend consumen en común es
`docs/contratos/servicios-de-dominio.md`: las firmas de los servicios de dominio
que los componentes Livewire invocan. Ese documento es el que habilita el
paralelismo entre las dos variantes de `implementation` — mientras una firma que
un sprint de frontend necesita no esté aprobada, ese sprint queda `BLOQUEADO`.

El contrato de SUNAT es **EXTERNO/REFERENCIADO**: Arquitectura no aprueba el
contrato del tercero, solo la forma de integrarlo, documentada en
`docs/integraciones/sunat.md`.

## Despacho de trabajo por rol

**El despacho por defecto de un sprint habilitado es un subagente**, no un chat. Lo despacha el
Coordinador con la herramienta de agentes: el subagente lee este `AGENTS.md`, el RFC del sprint
y su handoff, trabaja en su propio worktree y cierra declarando **exactamente un outcome** de
una lista cerrada —`terminado`/`parcial`/`bloqueado` para implementación y devops,
`aprobado`/`rechazado`/`bloqueado` para QA—. El contexto de un sprint no vive en una
conversación sino en el repositorio, así que no hace falta una sesión persistente para
ejecutarlo.

**Un chat de rol, que abre el usuario a mano, queda para tres casos:** un sprint cuyas
decisiones se resuelven conversando en vez de ejecutando; desbloquear lo que un subagente
devolvió como `bloqueado`; o cuando el usuario quiere seguir el razonamiento en vivo y no solo
recibir el resultado. Los prompts para esos casos están en `docs/despacho-de-roles.md`.

**Un subagente no tiene canal con el usuario.** Ante algo que exija su aprobación —un RFC, el
`AGENTS.md`, un waiver— o ante una ambigüedad con dos salidas de consecuencias distintas,
cierra `bloqueado` diciendo qué falta y quién lo resuelve. Esa es la diferencia real con un
chat: un chat pregunta, un subagente se detiene y devuelve el bloqueo. Por eso un sprint con
decisiones abiertas es mal candidato para despacho automático.

**Lo que no cambió:** el aislamiento por worktree, las rutas de escritura disjuntas por rol y
el despacho con dos anclas —`final_sha` del código y `gobierna: develop@<sha>`— rigen igual
para un subagente que para un chat.

La tabla siguiente sigue valiendo como reparto de responsabilidad por rol, se ejecute como
subagente o como chat.

| Rol | Quién es | Sprints que atiende |
|---|---|---|
| Coordinación + Arquitectura | `coordinacion` + `arquitectura` | todos: gobierna, aprueba y cierra |
| Backend | `implementation-backend` | S-00, S-01-B, S-02-B, S-03-B, S-04-B, S-05-B, S-06-B, S-07-B, S-08-B, S-09-B |
| Frontend | `implementation-frontend` | S-01-F, S-02-F, S-03-F, S-04-F, S-05-F, S-06-F |
| QA | `qa` | valida cada sprint; S-QA-01 es suyo de punta a punta |
| DevOps | `devops` | S-DO-01, S-DO-02 |

Ningún chat se abre por adelantado: se abre cuando su primer sprint está
habilitado. Hoy solo lo está el de Backend, para S-00.

## Permisos por área

**APROBADO el 2026-08-20**, al fusionar el PR #2. Las cuatro enmiendas están vigentes.

### Por qué

`AGENTS.md` declaraba los permisos como **lista de rutas conocidas al aprobarlo**. Cada
sprint materializa artefactos que esa lista no anticipó, así que el mismo desajuste
apareció una y otra vez, siempre a mitad de sprint y siempre deteniendo a alguien: las
rutas HTTP del servidor, el `README`, un registro compartido, y finalmente cinco
directorios de prueba.

Un inventario del árbol completo contra este documento encontró **32 archivos versionados
sin dueño**, escritos a lo largo de tres sprints por roles que no notaron estar fuera de
su declaración. No fue descuido de nadie: la lista no los cubría porque no existían cuando
se escribió.

Enumerarlos cerraría el estado de hoy y garantizaría que aparezca el siguiente. Por eso la
enmienda declara **áreas**, igual que se resolvieron los assets de Livewire por patrón en
vez de uno por uno, y los contratos por ADR en vez de enmienda por dominio.

### Qué cambia

| Rol | Se agrega |
|---|---|
| `implementation-backend` | Todo `tests/` excepto `tests/Feature/Livewire/`; todo `public/`; `pint.json` y los archivos de configuración de la raíz |
| `devops` | Todo `docker/` y `.dockerignore` |
| `coordinacion` | El campo `status` de la cabecera de los handoffs al cerrar un sprint; `docs/decisiones/` y `docs/despacho-de-roles.md` |
| `qa` | La práctica de mutación como obligación del rol, no como decisión registrada |
| `implementation-frontend` | `app/Compartido/Interfaz/`, para lo que comparten componentes de dominios distintos |

Además, los navegadores de validación pasan de "Chrome y Edge" a "motor Blink, declarando
cuál se usó".

### Qué desbloquea hoy

Cinco directorios de prueba con doce archivos dentro no tienen dueño: `tests/Feature/Autorizacion/`
(7), `tests/Feature/Fundacion/` (2), `tests/Feature/Interfaz/` (1), `tests/Soporte/` (1) y
`tests/recursos/` (1). Con ellos, doce pruebas que nadie puede reparar, el retiro de los
endpoints que exige ADR-0006, el mecanismo de permisos en componentes, y los sprints S-04-B
y S-02-F.

### Enmienda 2026-08-20 — `app/Compartido/Interfaz/` para `implementation-frontend`

`implementation-frontend` no tenía **ningún** sitio donde poner algo compartido entre
componentes de dominios distintos. Sus rutas son o de un dominio (`app/Dominios/*/Livewire/`)
o de presentación (`resources/`); lo transversal en PHP caía en `app/Compartido/`, que es de
`implementation-backend`.

Apareció al entregar S-02-F: el método que decide si un rechazo se muestra junto al campo o
como aviso de la operación quedó **byte a byte idéntico en las cuatro pantallas**. No lo
extrajeron por no invadir ruta ajena ni inventar un dominio falso, y lo señalaron en vez de
resolverlo por su cuenta.

**Esa lógica no es un detalle de implementación de una pantalla: es el consumo de una regla
transversal aprobada** — `docs/errores/manejo-errores.md` fija que un error de campo nombra su
campo en `detalle`, precisamente para que la pantalla sepa dónde ponerlo. Una regla con una
sola fuente no debería tener cuatro consumidores que la reimplementan.

Tampoco corresponde a `implementation-backend`: el método manipula estado de un componente
Livewire —qué campo quedó marcado, qué mensaje se muestra dónde— que son conceptos de la
interfaz. Ponerlo del otro lado le daría a backend código sobre el estado visual.

Es el mismo hueco que resolvió esta enmienda para los cinco directorios de prueba sin dueño,
un nivel más arriba: **un artefacto legítimo que ninguna área declarada cubre**. Con cuatro
copias es tolerable; S-03-F suma tres pantallas más.

### Qué NO cambia

- Ningún rol gana acceso a las rutas de otro. `tests/Feature/Livewire/` sigue siendo de
  `implementation-frontend`, y las rutas de interfaz siguen siendo suyas.
- La regla de fondo no se relaja: **una ruta no declarada sigue sin ser una ruta
  permitida**. Lo que cambia es que la declaración cubra áreas y no enumeraciones, para
  que dejen de aparecer huecos por artefactos nuevos.
- Nada de esto autoriza a un rol a escribir donde no le corresponde por contenido: si un
  sprint necesita tocar el área de otro, sigue escalando al Coordinador.

## Documentación de referencia
- Requisitos (RF/RNF): docs/requisitos/
- Glosario: docs/requisitos/glosario.md
- Actores y permisos: docs/requisitos/actores-permisos.md
- Contratos/API: docs/contratos/
- Frontend — experiencia/integración: docs/frontend/
- Persistencia: docs/persistencia/
- Manejo de errores: docs/errores/
- Arquitectura (visión general, opcional): no aplica — proyecto de un solo repositorio; la visión vive en el documento de diseño y en los ADR
- Integraciones externas: docs/integraciones/
- ADR: docs/decisiones/
- RFCs por sprint: docs/rfcs/
- Handoffs de sprint: docs/handoffs/
- Contrato entre backend y frontend: docs/contratos/servicios-de-dominio.md
- Despacho por rol, y prompts para los chats que sí se abren a mano: docs/despacho-de-roles.md
