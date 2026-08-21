# Límite residual del guardián de G2 — evidencia de la segunda validación

**Esto es evidencia de una observación no bloqueante, no un defecto abierto.** Lo encontró `qa`
al validar la re-entrega de S-07-B sobre `579c756`, y **aprobó igual**. Se rescata acá porque
era su única reproducción y su directorio de sesión no sobrevive — en este mismo sprint ya se
perdieron dos sondas por no rescatarlas a tiempo.

## Qué cubre el guardián, y qué no

El hueco **G2 declarado está cerrado**: un campo nuevo como `valor_en_riesgo` = cantidad × costo
lo pesca la lista blanca de claves, a cualquier escala. Eso se comprobó por mutación y se puso
rojo.

Lo que sobrevive son dos formas que exigen **corromper un campo ya declarado**, convirtiéndolo en
portador de un importe que no le corresponde:

| | Mutación | Por qué pasa |
|---|---|---|
| **G2c** | el importe embebido en un campo de texto ya declarado: `codigo_lote` → `"L-001#10111.1101"` | La segunda capa salta los valores no numéricos con `if (! is_numeric($valor)) continue;` |
| **G2d** | el mismo importe **redondeado a 2 decimales** —la forma natural de un monto en soles— dentro de `cantidad_actual` | `bccomp` compara a escala 4 contra el valor exacto: `10111.11` no iguala a `10111.1101`, y el costo se recupera dividiendo igual |

Las dos dejaron la suite entera en verde: **543/543 con 1192 aserciones**.

## Por qué `qa` no rechazó por esto

Ningún conjunto finito de aserciones sobre valores puede cubrir todas las codificaciones posibles
de un dato secreto. Exigir completitud ahí sería exigir lo imposible, y las dos formas que
sobreviven **no son fugas por descuido** sino violaciones visibles del significado de un campo:
alguien tendría que decidir meter un importe dentro de `cantidad_actual` o de `codigo_lote`.

## Lo que sí conviene ajustar algún día

**La redacción, no el mecanismo.** El guardián se describe como *«ningún valor de la fila permite
reconstruir el costo»* y lo que cumple es *«ningún valor iguala el costo ni el importe exacto a
escala 4»*. La promesa es más ancha que el mecanismo.

Es el patrón que este proyecto ya tiene registrado —una comprobación que parece verificar más de
lo que verifica— y acá aparece **sin consecuencia práctica**, porque el hueco declarado sí está
cerrado. Se anota para que nadie lea esa frase y derive una garantía que no existe.

## Cómo volver a correrlo

Necesita un worktree con `vendor/`, `node_modules/` y `.env`, y **`pnpm build` corrido antes** —
sin el manifiesto de Vite fallan 16 pruebas de la suite Feature por 500. La base de pruebas se
deriva sola desde el 2026-08-21: no hay que exportar `DB_DATABASE`.

Las dos mutaciones se aplican sobre el mapeo de `ConsultaDeInventarioService::lotesPorVencer()`,
en el bloque `->map(fn (Lote $lote) => [ … ])`:

```php
// G2c — el importe escondido dentro de un campo de texto declarado
'codigo_lote' => $lote->codigo_lote.'#'.bcmul($lote->cantidad_actual, $lote->costo_unitario, 4),

// G2d — el importe redondeado a 2 decimales dentro de un campo numérico declarado
'cantidad_actual' => bcmul($lote->cantidad_actual, $lote->costo_unitario, 2),
```

Con cualquiera de las dos, `php artisan test --testsuite=Feature` da **543/543 en verde**. Ese
verde *es* el hallazgo. Restaurar con `git checkout --` y confirmar que `git diff` queda vacío.

Para contraste, la mutación que **sí** se detecta —y que es el hueco G2 declarado— es agregar un
campo nuevo:

```php
'valor_en_riesgo' => bcmul($lote->cantidad_actual, $lote->costo_unitario, 4),
```
