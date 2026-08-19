# Glosario — ventas-inventario

Cada término tiene una sola definición. El glosario no es autoridad sobre
transiciones de estado ni permisos: eso vive en el RF correspondiente.

## Lote
- Definición: conjunto de unidades de un mismo producto que ingresaron juntas y comparten fecha de vencimiento y costo unitario. El stock del sistema se lleva por lote, no por producto.
- Usado en: RF-006, RF-007, RF-008, RF-012, RF-018, docs/persistencia/modelo.md
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## FEFO
- Definición: regla de salida de inventario que descuenta primero el lote con la fecha de vencimiento más próxima entre los no vencidos con existencia. Del inglés *first expired, first out*.
- Usado en: RF-012, RF-021
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Stock disponible
- Definición: suma de las cantidades de los lotes de un producto que no están vencidos y tienen existencia mayor a cero. Excluye los lotes vencidos aunque conserven unidades físicas.
- Usado en: RF-007, RF-012, RF-019
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Kardex
- Definición: registro histórico e inmutable de cada entrada, salida y ajuste de inventario, con su producto, lote, cantidad, costo, responsable y documento de origen.
- Usado en: RF-008, RF-009, RNF-004
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Comprobante
- Definición: documento electrónico que respalda una venta ante SUNAT. En esta versión son dos tipos: boleta de venta y factura.
- Usado en: RF-013, RF-014, RF-015, RF-016
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Boleta de venta
- Definición: comprobante dirigido al consumidor final. Admite cliente con DNI o venta a público general sin documento. Se informa a SUNAT mediante el resumen diario.
- Usado en: RF-010, RF-013, RF-017
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Factura
- Definición: comprobante dirigido a un cliente con RUC, que le permite sustentar crédito fiscal. Se envía a SUNAT individualmente.
- Usado en: RF-010, RF-013, RF-015
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Serie y correlativo
- Definición: identificación única de un comprobante. La serie agrupa los comprobantes de un tipo y el correlativo es el número consecutivo dentro de esa serie, sin repeticiones ni saltos.
- Usado en: RF-014, RNF-003
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## CDR
- Definición: constancia de recepción que devuelve SUNAT tras procesar un comprobante, y que indica si fue aceptado o rechazado. Es la evidencia de que el comprobante fue informado.
- Usado en: RF-015, RF-016
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Resumen diario
- Definición: envío agrupado que informa a SUNAT las boletas emitidas en un día. Cada boleta se incluye exactamente una vez.
- Usado en: RF-017
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## IGV
- Definición: impuesto general a las ventas, con tasa de 18 %, incluido en los precios que maneja el sistema y desglosado al emitir el comprobante.
- Usado en: RF-011, RNF-006
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Precio al por menor / precio al por mayor
- Definición: los dos precios de venta que tiene cada producto. El vendedor elige cuál aplica en cada línea de la venta, y la venta registra cuál se usó.
- Usado en: RF-004, RF-011, RF-020
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## Ajuste de inventario
- Definición: corrección manual de la cantidad de un lote, con motivo obligatorio, que no proviene de una compra ni de una venta.
- Usado en: RF-009, RNF-004
- Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19
