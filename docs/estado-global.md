---
project: ventas-inventario
source_status: CANONICA
baseline: documentación inicial aprobada 2026-08-19
active_phase: S-00
active_status: LISTO
last_completed_phase: null
bootstrap_status: PENDIENTE
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
    execution_status: LISTO
    depends_on: []
    parallelizable_with: []
  - id: S-01-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
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
    execution_status: PLANIFICADO
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

## Bloqueantes
- Ninguno para planificar ni para ejecutar. S-06-B se desarrolla y S-QA-01 valida contra el ambiente **beta**, con credenciales y certificado de prueba: no hacen falta datos del negocio.
- Condición futura, no bloqueante: el RUC real, la razón social, la dirección fiscal, el usuario SOL real y el certificado digital comprado se necesitan solo para el paso a producción, que exige autorización explícita del usuario. Ver `docs/integraciones/sunat.md`.

## Siguiente fase habilitada
- S-00 (fundación técnica): `Planificación: LISTO` y `Ejecución: LISTO`. Todos los demás sprints quedan en `PLANIFICADO` hasta que sus dependencias se completen.
- La ejecución no ha comenzado y requiere una instrucción explícita del usuario.

## Referencias
- Roadmap: este documento, sección "Roadmap del horizonte"
- Handoff activo: ninguno — no hay ejecución iniciada
- Decisiones y contratos: docs/decisiones/, docs/contratos/, docs/persistencia/modelo.md

---

# Roadmap del horizonte

Cada sprint declara qué produce de forma observable, de qué documentos
aprobados nace, de qué depende y con qué puede correr en paralelo. El sufijo
`-B` marca sprints de backend, `-F` de frontend, `-DO` de DevOps y `-QA` de
validación.

| ID | Rol | Resultado observable | Fuentes | Depende de | Paralelizable con | RFC |
|---|---|---|---|---|---|---|
| S-00 | implementation (backend) | Proyecto Laravel fundado y corriendo en local, con PostgreSQL, Tailwind vía Vite, la estructura `app/Dominios/`, y los comandos de lint, pruebas y build funcionando de verdad | ADR-0001, ADR-0005, AGENTS.md | — | — | docs/rfcs/S-00.md |
| S-DO-01 | devops | Entorno reproducible con Docker Compose: aplicación, PostgreSQL y proceso trabajador de cola, levantables con un comando | ADR-0001, ADR-0003, RNF-002 | S-00 | S-01-B | docs/rfcs/S-DO-01.md |
| S-01-B | implementation (backend) | Inicio y cierre de sesión, alta y edición de usuarios, y control de acceso por rol aplicado en el servidor sobre cada ruta | RF-001, RF-002, MIG-001, actores-permisos, contratos/usuarios | S-00 | S-DO-01 | docs/rfcs/S-01-B.md |
| S-02-B | implementation (backend) | Categorías y productos con precio menor y mayor, stock mínimo y su validación de precios | RF-003, RF-004, MIG-002, contratos/productos | S-01-B | S-03-B, S-01-F | docs/rfcs/S-02-B.md |
| S-03-B | implementation (backend) | Proveedores y clientes con validación de documento según su tipo | RF-005, RF-010, MIG-003, MIG-006, contratos/proveedores, contratos/clientes | S-01-B | S-02-B, S-01-F | docs/rfcs/S-03-B.md |
| S-04-B | implementation (backend) | Compras que crean lotes con vencimiento y costo, consulta de stock por lote, kardex inmutable y ajustes con motivo | RF-006, RF-007, RF-008, RF-009, MIG-004, MIG-005, ADR-0004, contratos/compras, contratos/inventario | S-02-B, S-03-B | S-02-F | docs/rfcs/S-04-B.md |
| S-05-B | implementation (backend) | Venta registrada en una transacción: descuento FEFO con reparto por lote, cálculo de IGV, reserva de correlativo y comprobante en estado pendiente | RF-011, RF-012, RF-013, RF-014, MIG-007, MIG-008, RNF-003, contratos/ventas | S-04-B | S-03-F | docs/rfcs/S-05-B.md |
| S-06-B | implementation (backend) | Emisión electrónica real contra el ambiente beta de SUNAT: XML firmado, envío en segundo plano con reintentos, constancia CDR guardada, reenvío manual y resumen diario de boletas | RF-015, RF-016, RF-017, ADR-0002, ADR-0003, integraciones/sunat, contratos/comprobantes | S-05-B | S-07-B, S-08-B, S-04-F | docs/rfcs/S-06-B.md |
| S-07-B | implementation (backend) | Consultas de alertas de vencimiento y stock bajo, y reportes de ventas y de utilidad con costo real por lote | RF-018, RF-019, RF-020, RF-021, contratos/inventario, contratos/ventas | S-05-B | S-06-B, S-08-B, S-04-F | docs/rfcs/S-07-B.md |
| S-08-B | implementation (backend) | Política completa de auditoría aplicada a cada operación sensible, y registro de errores con saneamiento de secretos y canal independiente | RNF-004, RNF-014, modelo (Auditoria, LogError) | S-04-B | S-06-B, S-07-B | docs/rfcs/S-08-B.md |
| S-01-F | implementation (frontend) | Base de la interfaz: layout, menú por rol, componentes reutilizables y pantalla de inicio de sesión | RF-001, RF-002, frontend/experiencia, frontend/integracion | S-01-B | S-02-B, S-03-B | docs/rfcs/S-01-F.md |
| S-02-F | implementation (frontend) | Pantallas de catálogo, proveedores, clientes y usuarios, con sus validaciones en el momento de escribir | RF-002, RF-003, RF-004, RF-005, RF-010, frontend/experiencia | S-02-B, S-03-B, S-01-F | S-04-B | docs/rfcs/S-02-F.md |
| S-03-F | implementation (frontend) | Pantallas de compra, consulta de inventario por lote, kardex y ajuste | RF-006, RF-007, RF-008, RF-009, frontend/experiencia | S-04-B, S-01-F | S-05-B | docs/rfcs/S-03-F.md |
| S-04-F | implementation (frontend) | Pantalla de caja completa: búsqueda por teclado y código de barras, precio menor o mayor por línea, confirmación e impresión del comprobante | RF-011, RF-012, RF-013, RNF-008, frontend/experiencia | S-05-B, S-01-F | S-06-B, S-07-B | docs/rfcs/S-04-F.md |
| S-05-F | implementation (frontend) | Pantalla de seguimiento de comprobantes con reenvío, y pantalla de resúmenes diarios | RF-016, RF-017, frontend/experiencia | S-06-B, S-01-F | S-06-F | docs/rfcs/S-05-F.md |
| S-06-F | implementation (frontend) | Tablero de alertas de vencimiento y stock bajo, y pantallas de reportes de ventas y utilidad | RF-018, RF-019, RF-020, RF-021, frontend/experiencia | S-07-B, S-01-F | S-05-F | docs/rfcs/S-06-F.md |
| S-09-B | implementation (backend) | Pruebas de extremo a extremo del recorrido de venta, y prueba de rendimiento que mide el umbral de confirmación de venta sobre volumen realista | RNF-001, RNF-008, AGENTS.md (comando E2E) | S-04-F | S-05-F, S-06-F | docs/rfcs/S-09-B.md |
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
