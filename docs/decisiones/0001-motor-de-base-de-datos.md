# ADR-0001: Motor de base de datos — PostgreSQL

- Estado: aceptada
- Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19

## Contexto

El sistema maneja dinero, stock por lote y correlativos de comprobantes que no
pueden repetirse. Eso exige transacciones reales, bloqueo de fila, aritmética
decimal exacta y restricciones que la base de datos garantice por sí misma
(RNF-003, RNF-006). El usuario no tiene una convención única: `hurioscan` usa
PostgreSQL y `reservas-canchas` usa MySQL, así que el proyecto no hereda un
motor por consistencia y hay que decidirlo.

## Decisión

PostgreSQL, en su versión estable vigente al momento de fundar el proyecto
(verificada contra la fuente oficial, no de memoria).

Los motivos concretos, en orden de peso:

1. `numeric` con precisión y escala exactas para importes y costos, sin recurrir a punto flotante.
2. `timestamptz`, que cumple el invariante de instantes en UTC por mecanismo del motor y no por configuración de sesión.
3. Restricciones `CHECK` y unicidad parcial (índice único con condición) que permiten garantizar en la base que un lote nunca quede negativo y que un correlativo no se repita por serie.
4. `SELECT ... FOR UPDATE` sobre la fila de la serie para reservar correlativos bajo concurrencia.
5. El usuario ya lo opera en `hurioscan`, así que no introduce un motor desconocido.

## Consecuencias

- El despliegue exige un servidor o servicio que ofrezca PostgreSQL. Queda descartado el hosting compartido económico que solo ofrece MySQL: si el despliegue termina siendo de ese tipo, este ADR debe revisarse antes de implementar, no después.
- Las pruebas corren contra PostgreSQL, no contra SQLite en memoria: las restricciones que garantizan la integridad del stock y de los correlativos son específicas del motor, y probarlas contra otro motor daría una confianza falsa.
- El costo de infraestructura es levemente mayor que el de un hosting compartido con MySQL.
