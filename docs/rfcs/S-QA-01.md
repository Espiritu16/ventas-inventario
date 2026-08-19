## RFC — S-QA-01 · Validación integral
- RF que implementa: ninguno. Valida todo el horizonte: RF-001 a RF-021 y RNF-001 a RNF-014.
- Rol: qa
- Alcance de este sprint: validación independiente con la skill `qa-validacion` sobre el `final_sha` exacto de los sprints ya integrados: funcional, negativa, de concurrencia, de permisos y de seguridad de aplicación, más la emisión real contra el ambiente **beta** de SUNAT. Produce un veredicto: APROBADO, RECHAZADO o BLOQUEADO. **Explícitamente no**: corregir lo que encuentre — cada corrección vuelve a su sprint bajo el rol de implementación.
- Documentos relacionados: todos los RF y RNF, docs/requisitos/actores-permisos.md, docs/errores/manejo-errores.md, docs/integraciones/sunat.md
- Contratos que toca: ninguno — QA no escribe implementación
- Estado: aprobado
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Unidades de trabajo
| ID | Resultado observable | Deriva de | Depende de | Paralelizable con | Interfaz fijada | Criterio de cierre |
|---|---|---|---|---|---|---|
| UT-01 | Validación funcional y negativa de todo el horizonte, contra los criterios de aceptación de cada RF | RF-001 a RF-021 | ninguna | UT-02, UT-03 | ninguna | cada criterio de aceptación queda verificado con evidencia reproducible, o reportado como fallo |
| UT-02 | Validación de permisos sobre cada operación, en sus cuatro combinaciones | RNF-013, actores-permisos | ninguna | UT-01, UT-03 | matriz de permisos aprobada | sin autenticar, con permiso, sin permiso y con credencial inválida se comportan según la matriz; cada condición de alcance se prueba cumplida y no cumplida |
| UT-03 | Validación de concurrencia e integridad: correlativos y stock bajo carga simultánea | RNF-003 | ninguna | UT-01, UT-02 | ninguna | dos ventas simultáneas sobre el mismo lote y la misma serie no producen stock negativo ni correlativo repetido |
| UT-04 | Validación de seguridad de aplicación con `seguridad-validacion`, proporcional a la superficie | RNF-010 a RNF-014 | UT-02 | ninguna | ninguna | hallazgos validados y deduplicados, con evidencia normalizada; ningún secreto aparece en logs ni en respuestas de error |
| UT-05 | Emisión verificada contra el ambiente beta de SUNAT, de extremo a extremo | RF-015, RF-017, RNF-002 | UT-01 | ninguna | ambiente beta únicamente | una factura y una boleta llegan a estado aceptado con su constancia guardada, y un resumen diario se envía y se consulta; con SUNAT simulado como caído, la venta se completa igual |
| UT-06 | Veredicto único de QA con su evidencia | todos | UT-01 a UT-05 | ninguna | handoff del sprint | el veredicto es APROBADO, RECHAZADO o BLOQUEADO, con la evidencia de cada punto; un reporte de herramienta no sustituye el veredicto |
