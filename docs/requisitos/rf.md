# Requisitos funcionales — ventas-inventario

Fuente: sesión de diseño del 2026-08-19. Todo RF nace en estado `propuesto` y
solo pasa a `aprobado` cuando el usuario real aprueba su contenido exacto.

Alcance de la primera versión: núcleo de catálogo, compras, inventario por
lotes, ventas con emisión electrónica propia ante SUNAT, y alertas.
**Fuera de alcance declarado:** notas de crédito y devoluciones, caja por
turno con arqueo, toma de inventario físico, multi-almacén y guías de
remisión. Cada uno exige su propio RF aprobado antes de implementarse.

---

## RF-001 — Autenticación de usuarios
- Descripción: el sistema permite iniciar y cerrar sesión con usuario y contraseña. Sin sesión activa, ninguna operación del sistema es accesible.
- Criterio de aceptación: con credenciales válidas se accede al sistema; con credenciales inválidas se rechaza sin revelar si falló el usuario o la contraseña; sin sesión, cualquier ruta redirige al inicio de sesión.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-002 — Roles de acceso
- Descripción: cada usuario tiene un rol: `administrador` o `vendedor`. El administrador accede a todo el sistema. El vendedor accede a la venta, la consulta de catálogo y la consulta de stock; no accede a compras, ajustes de inventario, gestión de usuarios ni reportes de utilidad.
- Criterio de aceptación: un usuario con rol `vendedor` que intenta acceder a compras, ajustes, usuarios o reportes de utilidad recibe un rechazo por falta de permiso, tanto desde la interfaz como invocando la ruta directamente.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-003 — Gestión de categorías
- Descripción: el administrador crea, edita y desactiva categorías para agrupar productos.
- Criterio de aceptación: una categoría desactivada no aparece al crear productos nuevos, pero los productos ya asociados a ella conservan su categoría y siguen operativos.
- Prioridad: media
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-004 — Gestión de productos
- Descripción: el administrador registra productos con código interno, nombre, categoría, unidad de medida, **precio de venta al por menor**, **precio de venta al por mayor**, stock mínimo y estado activo/inactivo. Todo producto se controla por lotes con fecha de vencimiento.
- Criterio de aceptación: no se puede registrar un producto sin precio menor; el precio mayor es obligatorio y no puede ser mayor que el precio menor; el código interno es único; un producto inactivo no puede venderse ni comprarse, pero conserva su historial y su stock visible.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-005 — Gestión de proveedores
- Descripción: el administrador registra proveedores con tipo y número de documento (RUC), razón social, dirección, teléfono y correo.
- Criterio de aceptación: el número de documento es único y se valida según su tipo (RUC de 11 dígitos); no se puede registrar una compra a un proveedor inexistente.
- Prioridad: media
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-006 — Registro de compra e ingreso de mercadería
- Descripción: el administrador registra una compra indicando proveedor, tipo y número del documento del proveedor, fecha, y una o más líneas. Cada línea indica producto, cantidad, costo unitario, **código de lote** y **fecha de vencimiento**. Al confirmar la compra, cada línea **crea o incrementa un lote** del producto y genera su movimiento de ingreso en el kardex.
- Criterio de aceptación: confirmada la compra, el stock del producto aumenta exactamente en la cantidad ingresada y existe un lote con esa fecha de vencimiento y ese costo; una compra con fecha de vencimiento ya pasada se rechaza; una compra sin líneas se rechaza; el documento del proveedor no se puede repetir para el mismo proveedor.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-007 — Consulta de stock por producto y por lote
- Descripción: cualquier usuario autenticado consulta el stock disponible de un producto y el detalle de sus lotes, con cantidad y fecha de vencimiento de cada uno, ordenados por vencimiento más próximo.
- Criterio de aceptación: el stock mostrado del producto es igual a la suma de las cantidades de sus lotes no vencidos y no agotados; los lotes vencidos se muestran identificados como tales y no suman al disponible para venta.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-008 — Kardex de movimientos
- Descripción: el sistema registra todo movimiento de inventario (ingreso por compra, salida por venta, ajuste) con fecha, producto, lote, cantidad, costo unitario, usuario responsable y el documento que lo originó. El administrador consulta el kardex filtrando por producto y rango de fechas.
- Criterio de aceptación: cada compra, venta y ajuste produce su movimiento correspondiente; el kardex no se puede editar ni borrar; la suma algebraica de los movimientos de un lote coincide con la cantidad actual de ese lote.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-009 — Ajuste manual de inventario
- Descripción: el administrador corrige la cantidad de un lote indicando la nueva cantidad y un motivo obligatorio (merma, rotura, vencimiento, error de conteo). El ajuste genera su movimiento en el kardex.
- Criterio de aceptación: no se puede ajustar sin motivo; no se puede dejar un lote en cantidad negativa; el ajuste queda registrado con el usuario que lo hizo y es visible en el kardex.
- Prioridad: media
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-010 — Gestión de clientes
- Descripción: el sistema registra clientes con tipo de documento (DNI, RUC, carné de extranjería, sin documento), número, nombre o razón social, dirección, teléfono y correo. Se puede registrar un cliente desde la propia pantalla de venta sin abandonarla.
- Criterio de aceptación: el número de documento se valida según su tipo (DNI 8 dígitos, RUC 11 dígitos) y es único cuando existe; **una factura exige un cliente con RUC**; una boleta admite cliente con DNI o venta a público general sin documento.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-011 — Registro de venta
- Descripción: el vendedor registra una venta agregando líneas de producto con cantidad y eligiendo por línea si aplica **precio al por menor o al por mayor**, selecciona el tipo de comprobante (boleta o factura), el cliente y el medio de pago (efectivo, tarjeta, billetera digital), y confirma. El sistema calcula el importe por línea, el total, y desglosa base imponible e IGV (18 %) a partir de precios que ya incluyen IGV.
- Criterio de aceptación: el total de la venta es la suma de los importes de línea; la suma de base imponible más IGV es igual al total; la venta queda registrada con el usuario que la hizo, la fecha y hora, y el precio efectivamente aplicado en cada línea; una venta sin líneas se rechaza.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-012 — Descuento de stock por vencimiento más próximo (FEFO)
- Descripción: al confirmar una venta, el sistema descuenta la cantidad de cada línea tomando primero el lote con **fecha de vencimiento más próxima** entre los lotes no vencidos con existencia, y continúa con los siguientes hasta cubrir la cantidad. El sistema registra de qué lote salió cada porción y con qué costo unitario.
- Criterio de aceptación: vendiendo una cantidad mayor a la del lote más próximo a vencer, la venta se reparte entre los lotes siguientes en orden de vencimiento y queda registrado el reparto exacto con el costo de cada porción; **un lote vencido nunca se descuenta**, aunque tenga existencia; si el stock disponible no vencido es insuficiente, la venta se rechaza íntegra indicando la cantidad disponible, sin descontar nada.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-013 — Emisión de comprobante electrónico
- Descripción: cada venta confirmada genera un comprobante electrónico —boleta o factura, según lo elegido en la venta— con su serie y correlativo, en estado inicial `PENDIENTE`. El comprobante se imprime y se entrega al cliente en el momento, sin esperar la respuesta de SUNAT.
- Criterio de aceptación: confirmada la venta, existe un comprobante con serie y correlativo asignados y estado `PENDIENTE`, y el documento impreso está disponible de inmediato; una venta nunca queda sin comprobante.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

### Ciclo de vida de Comprobante
- Estado inicial: PENDIENTE
- Estados terminales: ACEPTADO
- Reapertura: no permitida. Un comprobante `RECHAZADO` no vuelve a `PENDIENTE`; se corrige emitiendo uno nuevo, y la resolución del rechazado queda fuera del alcance de la v1 (requiere el RF de notas de crédito, no aprobado).

| Desde | Acción | Hacia | Actor autorizado | Precondición | Efectos | Error si no aplica |
|---|---|---|---|---|---|---|
| PENDIENTE | enviar | ENVIADO | sistema (proceso de envío) | comprobante con XML firmado | se registra fecha de envío e intento | TRANSICION_COMPROBANTE_INVALIDA |
| ENVIADO | aceptar | ACEPTADO | sistema (proceso de envío) | SUNAT devolvió constancia CDR conforme | se guarda el CDR y la fecha de aceptación | TRANSICION_COMPROBANTE_INVALIDA |
| ENVIADO | rechazar | RECHAZADO | sistema (proceso de envío) | SUNAT devolvió error de validación | se guarda el código y el mensaje de SUNAT | TRANSICION_COMPROBANTE_INVALIDA |
| ENVIADO | devolver a pendiente | PENDIENTE | sistema (proceso de envío) | el envío falló por causa transitoria (red, servicio no disponible) | se incrementa el contador de intentos y se reprograma | TRANSICION_COMPROBANTE_INVALIDA |

- Aprobado por: pendiente — fecha: pendiente

## RF-014 — Series y correlativos
- Descripción: el administrador configura las series de comprobante por tipo (factura, boleta). El sistema asigna el correlativo siguiente de forma automática al emitir, sin repetir ni saltar números.
- Criterio de aceptación: dos ventas confirmadas simultáneamente nunca reciben el mismo correlativo; el correlativo asignado es siempre el inmediato siguiente al último emitido de esa serie; no se puede emitir sobre una serie no configurada.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-015 — Envío del comprobante a SUNAT
- Descripción: tras registrar la venta, el sistema arma el XML del comprobante, lo firma con el certificado digital del emisor y lo envía a SUNAT en segundo plano, sin bloquear la caja. Ante fallas transitorias reintenta automáticamente con esperas crecientes; ante un rechazo por datos, no reintenta.
- Criterio de aceptación: con SUNAT no disponible, la venta se completa igual y el comprobante permanece `PENDIENTE` y se reintenta; recibida la constancia CDR conforme, el comprobante pasa a `ACEPTADO` y el CDR queda almacenado; recibido un rechazo, el comprobante pasa a `RECHAZADO` con el código y mensaje exactos de SUNAT y no se reintenta; **un mismo comprobante nunca se envía dos veces como documento nuevo**, aunque el proceso de envío se ejecute repetidas veces.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-016 — Seguimiento de comprobantes
- Descripción: el administrador consulta la relación de comprobantes con su estado ante SUNAT, filtrando por estado y por rango de fechas, y ve para cada uno el motivo exacto del rechazo cuando corresponde. Puede solicitar el reenvío manual de un comprobante `PENDIENTE`.
- Criterio de aceptación: la pantalla muestra los comprobantes `PENDIENTE` y `RECHAZADO` de forma destacada; cada rechazado muestra el código y el mensaje de SUNAT; el reenvío manual de un `PENDIENTE` reinicia el intento sin duplicar el documento; un comprobante `ACEPTADO` no se puede reenviar.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-017 — Resumen diario de boletas
- Descripción: el sistema envía a SUNAT, una vez al día, el resumen de las boletas emitidas, y registra su resultado. El administrador puede consultar los resúmenes enviados y su estado, y disparar el envío manualmente.
- Criterio de aceptación: cada boleta emitida queda incluida exactamente una vez en un resumen; el resumen registra el resultado devuelto por SUNAT; una boleta ya incluida en un resumen aceptado no se vuelve a incluir.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-018 — Alertas de vencimiento
- Descripción: el sistema muestra en el tablero los lotes cuya fecha de vencimiento está dentro de un plazo configurable (por defecto 30 días) y los ya vencidos con existencia, ordenados por urgencia.
- Criterio de aceptación: un lote que entra en el plazo aparece en la alerta sin intervención manual; un lote vencido con existencia aparece señalado como vencido y su cantidad se excluye del stock disponible para venta; el plazo se puede cambiar y la lista se recalcula.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-019 — Alertas de stock bajo
- Descripción: el sistema muestra en el tablero los productos cuyo stock disponible es menor o igual a su stock mínimo configurado.
- Criterio de aceptación: al caer el stock disponible al mínimo o por debajo, el producto aparece en la alerta; al reponerse por una compra, desaparece; los productos inactivos no se listan.
- Prioridad: alta
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-020 — Reporte de ventas del período
- Descripción: el administrador consulta las ventas de un rango de fechas, con su total, el desglose por tipo de comprobante y por medio de pago, y el detalle de cada venta.
- Criterio de aceptación: el total del reporte coincide con la suma de las ventas del rango; el reporte distingue las ventas cuyo comprobante fue rechazado por SUNAT.
- Prioridad: media
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente

## RF-021 — Reporte de utilidad
- Descripción: el administrador consulta, para un rango de fechas, el ingreso por ventas, el costo real de la mercadería vendida —tomado del costo del lote del que salió cada unidad— y la utilidad resultante, en total y por producto.
- Criterio de aceptación: el costo reportado de una venta es la suma de los costos de las porciones de lote efectivamente descontadas en esa venta, no un costo promedio del producto; la utilidad es ingreso menos ese costo.
- Prioridad: media
- Estado: propuesto
- Aprobado por: pendiente — fecha: pendiente
