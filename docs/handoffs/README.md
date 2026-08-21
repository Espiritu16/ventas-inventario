# Handoffs de sprint

Un archivo por sprint, con el nombre del sprint: `S-00.md`, `S-01-B.md`, etc.

Lo crea y lo mantiene **el chat que ejecuta ese sprint**, y solo ese: nadie
escribe en el handoff de otro. El Coordinador es quien registra ahí el resumen
de QA y de DevOps, y el único que actualiza `docs/estado-global.md`.

Los que existen hoy están listados abajo. Un sprint sin handoff es un sprint que no
se ejecutó.

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

## Handoffs existentes

`S-00`, `S-01-B`, `S-01-F`, `S-02-B`, `S-02-F`, `S-03-B`, `S-04-B`, `S-05-B`, `S-DO-01`,
`S-07-B`.

> **Esta lista se mantiene a mano y por eso vale desconfiar de ella.** La fuente que no
> envejece es `ls docs/handoffs/`. Se conserva porque le ahorra una lectura a quien llega —
> incluido un subagente, que reconstruye contexto desde archivos y no desde una conversación —
> pero ante cualquier diferencia manda el directorio.
>
> Este archivo afirmó durante nueve sprints que la ejecución no había comenzado. Nada falló al
> leerlo, que es la forma exacta que este proyecto lleva nueve instancias registrando.
