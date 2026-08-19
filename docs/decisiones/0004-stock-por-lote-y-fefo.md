# ADR-0004: El stock vive en los lotes, con saldo materializado y kardex inmutable

- Estado: aceptada
- Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19

## Contexto

La mercadería tiene fecha de vencimiento, así que el sistema no puede llevar
un único número de stock por producto: necesita saber qué unidades vencen
primero para venderlas antes (FEFO) y cuánto costó realmente lo que se vendió.
Había dos formas de sostenerlo: guardar el saldo de cada lote y actualizarlo
en cada movimiento, o no guardar saldo alguno y calcularlo sumando el
historial cada vez.

## Decisión

Cada lote guarda su cantidad actual, y toda entrada, salida o ajuste queda
además registrado en el kardex como historial inmutable. El stock disponible
de un producto es la suma de sus lotes no vencidos con existencia; no se
guarda un saldo redundante a nivel de producto.

## Consecuencias

- Consultar stock y decidir el reparto FEFO de una venta es una lectura barata, sin recorrer años de movimientos.
- Saldo e historial podrían divergir si alguna escritura ocurriera fuera de la transacción que actualiza ambos. Se previene de dos formas: toda escritura de inventario pasa por el servicio de inventario dentro de una única transacción, y una restricción de la base impide que un lote quede negativo.
- El kardex no se edita ni se borra (RNF-004); una corrección se hace con un ajuste nuevo, nunca modificando el historial.
- El costo de lo vendido sale del costo del lote del que salió cada porción, no de un promedio del producto, lo que permite el reporte de utilidad real de RF-021.
