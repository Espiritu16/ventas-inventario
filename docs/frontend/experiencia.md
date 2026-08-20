# Experiencia frontend — ventas-inventario

Comportamiento observable de la interfaz. Deriva de los RF aprobados, la matriz
de permisos y el glosario. Lo que tiene más de una solución válida queda
señalado como propuesto en la propia fila.

| Flujo | Ruta UI | Actor | Deriva de | Entrada/disparador | Estados visibles | Navegación esperada | Criterio verificable |
|---|---|---|---|---|---|---|---|
| Iniciar sesión | /login | anónimo | RF-001 | apertura del sistema o sesión expirada | idle, enviando, error de credenciales | tras iniciar sesión va a /panel; una URL pedida antes del login se recupera después | credenciales inválidas muestran un solo mensaje sin decir si falló usuario o contraseña; el foco vuelve al campo de correo |
| Ver tablero | /panel | administrador, vendedor | RF-018, RF-019 | tras iniciar sesión, o desde el menú | carga, vacío ("sin alertas"), error, éxito | es la pantalla de inicio de ambos roles | los lotes por vencer aparecen ordenados por urgencia y los vencidos se distinguen visualmente de los que aún no vencen; el vendedor no ve indicadores de utilidad |
| Registrar venta | /ventas/nueva | administrador, vendedor | RF-011, RF-012, RF-013 | desde el menú o atajo de teclado | idle, buscando producto, línea agregada, confirmando, éxito con comprobante listo, error | tras confirmar permanece en la misma pantalla, lista para la venta siguiente; no navega a otro lado | la venta se opera completa con teclado (RNF-008); tras confirmar, el comprobante se ofrece para imprimir sin pasos adicionales; el stock insuficiente se informa antes de cobrar, indicando la cantidad disponible |
| Buscar producto en la caja | /ventas/nueva | administrador, vendedor | RF-011, RNF-008 | escribir código o nombre, o leer código de barras | buscando, sin resultados, resultados | no cambia de ruta | un código de barras leído agrega la línea sin pasos extra; sin resultados se avisa sin borrar lo ya escrito |
| Ver ventas | /ventas | administrador, vendedor | RF-011, RF-020 | menú | carga, vacío, error, éxito | el detalle abre en /ventas/{id} y el botón atrás regresa conservando filtros y página | el vendedor solo ve las ventas que él registró; una venta ajena responde igual que una inexistente |
| Seguimiento de comprobantes | /comprobantes | administrador | RF-016 | menú, o aviso del panel cuando hay pendientes o rechazados | carga, vacío, error, éxito | el reenvío ocurre sin salir de la pantalla y actualiza la fila | los pendientes y rechazados aparecen primero y se distinguen a simple vista; cada rechazado muestra el motivo textual de SUNAT; el botón de reenvío solo está disponible en los pendientes |
| Resúmenes diarios | /resumenes-diarios | administrador | RF-017 | menú | carga, vacío, error, éxito | el envío manual actualiza la fila sin recargar | un día ya resumido y aceptado no ofrece volver a enviarse |
| Registrar compra | /compras/nueva | administrador | RF-006 | menú | idle, línea agregada, confirmando, éxito, error | tras confirmar va al detalle de la compra registrada | cada línea exige código de lote y vencimiento antes de poder agregarse; una fecha de vencimiento pasada se rechaza en el momento, no al confirmar |
| Consultar inventario | /inventario | administrador, vendedor | RF-007 | menú | carga, vacío, error, éxito | el detalle de lotes se expande en la misma fila | los lotes se listan por vencimiento más próximo y los vencidos aparecen marcados y excluidos del disponible; el vendedor no ve el costo |
| Consultar kardex | /inventario/kardex | administrador | RF-008 | desde el detalle de un producto | carga, vacío, error, éxito | conserva el producto seleccionado al cambiar el rango de fechas | cada movimiento muestra su documento de origen y el usuario responsable |
| Ajustar inventario | /inventario/ajustes | administrador | RF-009 | desde el detalle de un lote | idle, confirmando, éxito, error | vuelve al inventario con el lote actualizado | el motivo es obligatorio antes de poder confirmar; una cantidad que dejaría el lote negativo se rechaza |
| Gestionar catálogo | /productos, /categorias | administrador | RF-003, RF-004 | menú | carga, vacío, error, éxito | alta y edición en la misma pantalla, sin cambiar de ruta | un precio mayor superior al menor se rechaza en el momento de escribirlo, no al guardar |
| Gestionar clientes | /clientes | administrador, vendedor | RF-010 | menú, o desde la pantalla de venta | idle, confirmando, éxito, error | creado desde la venta, regresa a la venta con el cliente ya seleccionado | el número de documento se valida según su tipo antes de poder guardar |
| Gestionar proveedores | /proveedores | administrador | RF-005 | menú | carga, vacío, error, éxito | alta y edición en la misma pantalla | un RUC con longitud distinta de 11 se rechaza en el momento |
| Gestionar usuarios | /usuarios | administrador | RF-002 | menú | carga, vacío, error, éxito | alta y edición en la misma pantalla | un administrador no puede quitarse su propio rol ni desactivarse |
| Configurar series de comprobante | /series-comprobante | administrador | RF-014 | menú, o el aviso que aparece cuando no hay ninguna serie configurada | carga, vacío ("sin series configuradas"), confirmando, éxito, error | alta y desactivación en la misma pantalla, sin cambiar de ruta | el correlativo actual de cada serie es visible y **no editable a mano**; el sistema avisa que no hay serie configurada **antes** de la primera venta, no al confirmarla |
| Reportes | /reportes/ventas, /reportes/utilidad | administrador | RF-020, RF-021 | menú | carga, vacío, error, éxito | el rango de fechas queda en la URL y sobrevive a recargar y al botón atrás | el reporte de utilidad muestra el costo real por lote, no un promedio |

## Reglas transversales de la interfaz

- **Permisos**: el menú solo muestra lo que el rol puede usar, pero eso es comodidad, no seguridad. Toda ruta vuelve a verificar el permiso en el servidor; entrar por URL directa a algo no permitido responde con el error de autorización, no con una pantalla vacía.
- **Sesión expirada**: cualquier acción con sesión vencida lleva a `/login` conservando la URL pedida, y tras iniciar sesión regresa a ella.
- **Errores**: se muestran junto al campo cuando son de validación, y como aviso de la operación cuando son de negocio. El texto es el mensaje en español de la taxonomía; el código nunca se muestra al usuario, pero sí se registra.
- **Navegación**: recargar y usar atrás/adelante nunca repite una operación que escribe. Tras confirmar una venta o una compra, recargar no la duplica.
- **Formato**: importes con dos decimales y separador de miles, fechas en formato peruano (día/mes/año) y horas en zona de Lima.

Estado: aprobado
Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19
