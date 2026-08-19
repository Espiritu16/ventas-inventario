# AGENTS.md — ventas-inventario

> Define permisos, ramas y convenciones locales que project-continuity debe
> respetar. No redefine el proceso general de trabajo (vive en la skill);
> solo declara lo específico de este repositorio.

## Proyecto
- Proyecto: Sistema de ventas e inventario para comercializadora
- Identificador canónico del repositorio: ventas-inventario
- Tipo de repositorio: fullstack (backend Laravel + UI Blade/Livewire en el mismo repositorio)
- Stack/framework: PHP + Laravel + Livewire + Tailwind CSS; Greenter para emisión electrónica SUNAT — fuente: decisión del usuario en la sesión de diseño (2026-08-19)
- Runtime y versión: previsto en sprint de fundación (S-00): PHP 8.5.x (verificado en el entorno local: 8.5.9) y la última versión estable de Laravel compatible, verificada contra la fuente oficial al fundar
- Gestor de paquetes/build: Composer 2.10.2 (PHP) verificado en el entorno local; pnpm para los assets de frontend vía Vite — previsto en S-00
- Persistencia/motor: previsto en S-00 — motor relacional con transacciones y bloqueo de fila (decisión pendiente de ADR: PostgreSQL o MySQL 8)
- Estado global / roadmap: docs/estado-global.md — previsto en la planificación del horizonte
- Handoffs de sprint: docs/handoffs/<id-sprint>.md — previsto en el primer sprint con entrega

## Vigencia de gobernanza
- Estado de gobernanza: APROBADO
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com)
- Fecha de aprobación: 2026-08-19

## Roles activos en este repositorio

### coordinacion
- Puede escribir gobernanza y planificación: `AGENTS.md`, `docs/estado-global.md`, `docs/rfcs/`, `docs/handoffs/` (solo las secciones de resultados QA/DevOps)
- Puede redactar borradores documentales dentro de: `docs/requisitos/`, `docs/frontend/experiencia.md`, `docs/integraciones/`, `README.md`
- No puede: implementar código, autoaprobar al usuario, sustituir la aprobación de Arquitectura, emitir el veredicto QA ni ejecutar trabajo DevOps

### implementation
- Puede escribir código y pruebas: `app/`, `resources/`, `routes/`, `database/`, `tests/`, `config/`, `public/`
- Puede escribir bootstrap/configuración cuando el RFC lo autoriza: `composer.json`, `composer.lock`, `package.json`, `pnpm-lock.yaml`, `vite.config.js`, `.env.example` (sin secretos), `database/migrations/`
- Puede actualizar únicamente este handoff: `docs/handoffs/<id-de-su-propio-sprint>.md`; nunca el estado global ni handoffs ajenos
- No puede modificar sin autorización de Arquitectura: `docs/contratos/`, `docs/persistencia/`, `docs/errores/`, `docs/requisitos/actores-permisos.md`, `docs/frontend/integracion.md`, `docs/decisiones/`

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
- integración: ninguna, se integra directo a la protegida
- trabajo: `sprint/<id>`, `feature/<nombre>`, `fix/<nombre>`
- Entrega de Implementación: rama local sin fusionar + `final_sha` + handoff (no hay remoto configurado todavía)
- Gate antes de integrar: QA APROBADO sobre ese `final_sha` cuando el sprint requiere QA; solo el Coordinador integra y cierra

## CI por rama
- no aplica — sin pipeline configurado. Si se configura un remoto y CI, esta sección se completa en el sprint que lo introduzca y `AGENTS.md` vuelve a BORRADOR para reaprobación.

## Convención de commits
- Formato: `<tipo>(<alcance>): <descripción corta>`
  tipos válidos: feat, fix, chore, docs, test, refactor, style, perf
- Descripción y cuerpo en español, en pasado impersonal ("se agregó", "se corrigió"); el prefijo de tipo va en inglés por ser estándar
- Referenciar el sprint en el cuerpo: `Sprint: <id-sprint>`
- Un commit = un cambio lógico coherente
- Sin emojis salvo pedido explícito; sin línea `Co-Authored-By`
- Prohibido: commit directo a `main` fuera del cierre de sprint que hace el Coordinador
- Prohibido: `--force` push a `main` sin autorización explícita

## Comandos de verificación
- Lint: previsto en S-00: `./vendor/bin/pint --test`
- Tests unitarios/componentes: previsto en S-00: `php artisan test --testsuite=Unit`
- Integración/contrato: previsto en S-00: `php artisan test --testsuite=Feature`
- E2E: no aplica — sin herramienta E2E decidida; se reevalúa cuando exista la pantalla de caja
- Accesibilidad: no aplica — sin umbral de accesibilidad acordado todavía; se reevalúa al documentar `docs/frontend/experiencia.md`
- Build: previsto en S-00: `pnpm build`
- Otros RNF: previsto en el sprint de emisión electrónica: envío de comprobante contra el ambiente **beta** de SUNAT con verificación de CDR

## Validación QA
- Entorno autorizado: local, con base de datos de pruebas dedicada
- Identidades/datos de prueba: seeders del repositorio y el RUC/certificado de **pruebas** de SUNAT; nunca el certificado digital real ni secretos
- Evidencia durable: `docs/handoffs/<id-sprint>.md` bajo responsabilidad del Coordinador
- Retención de artefactos externos: no aplica — sin CI ni almacenamiento externo
- Navegadores/viewports requeridos: previsto al documentar `docs/frontend/experiencia.md`
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
