## RFC — S-DO-02 · Despliegue, respaldos y operación
- RF que implementa: ninguno. Cubre RNF-002, RNF-007 y las obligaciones de operación que `AGENTS.md` declara como previstas al definir el despliegue.
- Rol: `devops`
- Alcance de este sprint: poner el sistema en el servidor y dejarlo operable — integración continua en cada pull request, despliegue reproducible, respaldo diario **probado**, verificación de salud, recolección de logs y procedimiento de reversión. **Explícitamente no**: enviar comprobantes al ambiente de producción de SUNAT ni instalar el certificado real; eso exige autorización explícita del dueño del negocio, por separado.
- Documentos relacionados: RNF-002, RNF-005, RNF-007, RNF-014, AGENTS.md (operación DevOps/Release), ADR-0001, ADR-0003
- Contratos que toca: ninguno
- Estado: aprobado
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Unidades de trabajo
| ID | Resultado observable | Deriva de | Depende de | Paralelizable con | Interfaz fijada | Criterio de cierre |
|---|---|---|---|---|---|---|
| UT-01 | Integración continua en GitHub Actions: lint, pruebas unitarias, de integración y build en cada pull request | AGENTS.md (CI por rama) | ninguna | UT-02 | workflow en `.github/workflows/` | un pull request con una prueba rota no puede integrarse; el resultado queda visible en el PR |
| UT-02 | Proveedor y servidor elegidos y documentados, con PostgreSQL disponible | ADR-0001 | ninguna | UT-01 | `AGENTS.md`, sección de operación | el proveedor queda registrado; si el elegido no ofrece PostgreSQL, se reabre ADR-0001 antes de continuar, no se cambia de motor sobre la marcha |
| UT-03 | Despliegue reproducible de la aplicación y del proceso trabajador de la cola, con migraciones controladas | ADR-0003, AGENTS.md | UT-02 | ninguna | procedimiento de despliegue documentado | el proceso trabajador queda corriendo y se reinicia solo; las migraciones se ejecutan de forma controlada, nunca automática sobre datos reales |
| UT-04 | Respaldo diario automático con 30 días de retención y **restauración probada** | RNF-007 | UT-02 | UT-05 | procedimiento de respaldo y restauración | una restauración sobre una base vacía reproduce los datos; sin esa prueba, el respaldo no se considera cumplido |
| UT-05 | Verificación de salud, recolección de logs y procedimiento de reversión | RNF-002, RNF-014, AGENTS.md | UT-03 | UT-04 | endpoint de salud y procedimiento documentado | la verificación detecta la caída del proceso trabajador, que es el fallo silencioso más probable; la salida de logs se recolecta de verdad, cerrando lo que S-08-B/UT-03 dejó verificado |
| UT-06 | Custodia del certificado digital en el servidor, fuera del repositorio | RNF-005 | UT-03 | ninguna | variables `SUNAT_CERTIFICADO_RUTA` y `SUNAT_CERTIFICADO_CLAVE` | el certificado vive fuera del árbol del proyecto y no aparece en ningún log; el ambiente permanece en `beta` hasta autorización explícita del usuario |
