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
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — reaprobado el 2026-08-19 tras adoptar la política de ramas de dos niveles (`sprint/<id>` → `develop` → `main`)
- Fecha de aprobación: 2026-08-19

## Roles activos en este repositorio

### coordinacion
- Puede escribir gobernanza y planificación: `AGENTS.md`, `docs/estado-global.md`, `docs/rfcs/`, `docs/handoffs/` (solo las secciones de resultados QA/DevOps)
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
- Puede escribir código y pruebas: `app/Dominios/*/` **excepto** la subcarpeta `Livewire/` de cada dominio, `app/Compartido/`, `app/Http/Middleware/`, `app/Providers/`, `app/Jobs/`, `app/Console/`, `database/`, `config/`, `tests/Unit/`, `tests/Feature/Dominios/`
- Puede escribir bootstrap/configuración cuando el RFC lo autoriza: `composer.json`, `composer.lock`, `.env.example` (sin secretos), `database/migrations/`, y el andamiaje que el framework exige y ningún otro rol cubre: `bootstrap/`, `public/index.php`, `artisan`, `phpunit.xml`, `routes/console.php`, `.gitignore`
- Es el dueño de las firmas declaradas en `docs/contratos/servicios-de-dominio.md`: puede proponer cambios, pero la aprobación es de Arquitectura
- No puede escribir: `resources/views/`, `resources/css/`, `resources/js/`, `routes/web.php`, ni ninguna subcarpeta `Livewire/` — **salvo en S-00**, donde crea el andamiaje inicial de esas rutas (entry points de Vite, layout base vacío y `routes/web.php` con la ruta raíz), tal como declara la excepción del encabezado de este rol. A partir de S-01-F, esas rutas pasan a ser exclusivas de `implementation-frontend`

#### implementation-frontend (sprints con sufijo `-F`)
- Puede escribir código y pruebas: `app/Dominios/*/Livewire/`, `resources/views/`, `resources/css/`, `resources/js/`, `routes/web.php`, `tests/Feature/Livewire/`
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
- Puede ejecutar: los comandos de verificación declarados abajo y crear artefactos desechables fuera del árbol objetivo
- No puede escribir ni commitear durante la validación. Automatización o fixtures versionados requieren un sprint separado bajo rol `implementation`; QA puede definir los casos
- No puede: hacer merge, modificar implementación para pasar pruebas

### arquitectura
- Única autoridad sobre: contratos/interfaces, schema de persistencia, ADR, manejo de errores (formato y taxonomía), actores/permisos sobre cada ruta web, comando, job y cola, e integración frontend técnica
- Puede escribir/proponer dentro de: `docs/contratos/`, `docs/persistencia/`, `docs/errores/`, `docs/requisitos/actores-permisos.md`, `docs/frontend/integracion.md`, `docs/integraciones/sunat.md` (parte técnica), `docs/decisiones/`
- No puede: implementar código por ejercer Arquitectura, autoaprobar decisiones reservadas al usuario, sustituir QA ni cerrar sprints

### devops
- Debe usar la skill `devops-entrega` y reportar sobre `repositorio@final_sha` + artefacto/digest exactos
- Puede escribir: `Dockerfile`, `docker-compose.yml`, `.github/workflows/`, scripts de build/deploy, configuración operativa
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
- Integración/contrato: `php artisan test --testsuite=Feature` — real y en verde desde S-00. La suite aborta si la base configurada no termina en `_test`, para que una configuración equivocada no destruya la base de aplicación pasando en verde
- E2E: no aplica — sin herramienta E2E decidida; se reevalúa cuando exista la pantalla de caja
- Accesibilidad: previsto en S-09-B: recorrido completo de la venta operable solo con teclado, verificado de forma automatizada (RNF-008)
- Build: `pnpm build` — real y en verde desde S-00
- Otros RNF: previsto en S-06-B: envío de comprobante contra el ambiente **beta** de SUNAT con verificación de CDR. Rendimiento (RNF-001) y operación con teclado (RNF-008): previsto en S-09-B

## Validación QA
- Entorno autorizado: local, con base de datos de pruebas dedicada
- Identidades/datos de prueba: seeders del repositorio y el RUC/certificado de **pruebas** de SUNAT; nunca el certificado digital real ni secretos
- Evidencia durable: `docs/handoffs/<id-sprint>.md` bajo responsabilidad del Coordinador
- Retención de artefactos externos: no aplica — sin CI ni almacenamiento externo
- Navegadores/viewports requeridos: Chrome y Edge de escritorio, viewport mínimo 1366x768. La caja no se opera desde móvil (RNF-008: operación con teclado y lector de código de barras)
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

## Chats de rol previstos

El usuario abre estos chats a mano cuando empiece la ejecución. Los prompts de
apertura ya están redactados en `docs/chats-de-rol.md`.

| Chat | Rol | Sprints que atiende |
|---|---|---|
| Coordinación + Arquitectura | `coordinacion` + `arquitectura` | todos: gobierna, aprueba y cierra |
| Backend | `implementation-backend` | S-00, S-01-B, S-02-B, S-03-B, S-04-B, S-05-B, S-06-B, S-07-B, S-08-B, S-09-B |
| Frontend | `implementation-frontend` | S-01-F, S-02-F, S-03-F, S-04-F, S-05-F, S-06-F |
| QA | `qa` | valida cada sprint; S-QA-01 es suyo de punta a punta |
| DevOps | `devops` | S-DO-01, S-DO-02 |

Ningún chat se abre por adelantado: se abre cuando su primer sprint está
habilitado. Hoy solo lo está el de Backend, para S-00.

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
- Prompts de apertura de chats de rol: docs/chats-de-rol.md
