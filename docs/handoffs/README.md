# Handoffs de sprint

Un archivo por sprint, con el nombre del sprint: `S-00.md`, `S-01-B.md`, etc.

Lo crea y lo mantiene **el chat que ejecuta ese sprint**, y solo ese: nadie
escribe en el handoff de otro. El Coordinador es quien registra ahí el resumen
de QA y de DevOps, y el único que actualiza `docs/estado-global.md`.

Todavía no hay ninguno: la ejecución no ha comenzado. El primero será `S-00.md`.

Estructura, según `project-continuity`:

```markdown
---
id: S-00
name: Fundación técnica
role: implementation-backend
repository: ventas-inventario
status: EN_PROGRESO
branch: sprint/S-00
base_sha: <sha del que partió>
final_sha: null
worktree_path: <ruta o null>
updated_at: <fecha>
---

## Objetivo
## Implementado
## Checkpoints
## Evidencia de verificación
## Resultado QA
## Pendientes o desviaciones
```

Si el sprint repartió sus unidades entre varios subagentes, el handoff incluye
además la tabla de seguimiento de unidades, que es obligatoria en ese caso.
