# Evidencia del bloqueo de S-07-B

**Esto es evidencia, no cobertura.** Son sondas desechables que `qa` escribió para demostrar
sus hallazgos al validar S-07-B, rescatadas antes de que su directorio de sesión desapareciera.

**No viven en `tests/` a propósito.** Un artefacto de QA es desechable por definición; convertir
una comprobación en cobertura permanente es trabajo de `implementation`, con sus rutas
declaradas y su propio RFC. Si se decide que estas deben ser permanentes, **eso es un sprint, no
un rescate.** Llevan extensión `.php.txt` para que ningún corredor de pruebas las tome por
accidente.

| Archivo | Qué demuestra |
|---|---|
| `sonda-G1-rango-de-fechas.php.txt` | **G1**: `VentaService::reporteUtilidad()` no tiene ninguna cobertura de su rango de fechas. Pasa sobre el código limpio y falla mutado |
| `sonda-comprobaciones-de-qa.php.txt` | Las comprobaciones que **sí salieron bien**: tipos decimales, proyección por rol, y la réplica con el reloj en la mañana que dejó invisible la mutación de zona horaria |

**G2 no tiene sonda**: se demostró agregando un campo al código de producción y viendo pasar la
suite entera, no con un archivo aparte. Cómo reproducirlo está descrito en la sección REPOSO de
`docs/estado-global.md`.

## Cómo volver a correrlas

Necesitan un worktree del repositorio con `vendor/`, `node_modules/` y `.env`, y **`pnpm build`
corrido antes** — sin el manifiesto de Vite fallan 16 pruebas de la suite Feature por 500.

1. Copiar el archivo a `tests/Feature/` quitándole el `.txt` del final.
2. Apuntar la base de datos del carril por variable de entorno: `phpunit.xml` fija
   `ventas_inventario_test` y hay que pisarlo con `DB_DATABASE=<carril>_test`, no editando el
   archivo. La suite aborta si el nombre efectivo no termina en `_test`, y lo determina
   preguntándoselo al motor.
3. Correr, y **borrar el archivo al terminar**: no pertenece al árbol.

La sonda de G1 solo tiene valor **mutada**: sobre código limpio pasa. Para verla fallar hay que
quitar el `whereBetween('ventas.fecha')` del agregado de costo, del de ingreso, o de los dos.
