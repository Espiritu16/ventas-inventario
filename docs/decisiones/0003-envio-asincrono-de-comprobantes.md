# ADR-0003: El envío a SUNAT no bloquea la venta

- Estado: aceptada
- Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, sobre la decisión de negocio tomada por Kevin Espíritu — fecha: 2026-08-19

## Contexto

El negocio tiene un solo punto de venta. Si la confirmación de una venta
esperara la respuesta de SUNAT, cualquier lentitud o caída del servicio
—que ocurren, y suelen concentrarse a fin de mes— dejaría al cliente esperando
en el mostrador y podría impedir cerrar la venta. Con emisión propia, esa
disponibilidad es responsabilidad del sistema y no de un tercero contratado.

## Decisión

La venta se confirma, descuenta stock, reserva su correlativo y crea el
comprobante en estado `PENDIENTE` dentro de una sola transacción, y se imprime
de inmediato. El envío a SUNAT ocurre después, fuera de esa transacción, en un
trabajo en segundo plano con reintentos de espera creciente ante fallas
transitorias, y sin reintento ante rechazo por datos.

## Consecuencias

- La caja nunca se traba por causas ajenas (RNF-002).
- El despliegue necesita un proceso trabajador corriendo permanentemente además del servidor web; si ese proceso se cae, los comprobantes se acumulan en `PENDIENTE` sin que la venta se entere. Por eso la pantalla de seguimiento de comprobantes (RF-016) no es un accesorio: es la única forma de detectar esa acumulación.
- El envío debe ser idempotente: un mismo comprobante nunca puede enviarse dos veces como documento nuevo, aunque el trabajo se ejecute repetidas veces.
- Un comprobante rechazado por SUNAT no se corrige en esta versión, porque corregirlo exige notas de crédito, que están fuera de alcance.
