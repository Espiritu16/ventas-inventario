# Integración frontend — ventas-inventario

Cómo cada flujo de la interfaz consume las operaciones del backend. La fuente
canónica de cada operación es su contrato en `docs/contratos/`; acá no se
repiten schemas, se mapean.

Este proyecto es un único repositorio con Livewire: no hay cliente HTTP contra
una API externa, sino componentes que invocan los servicios de dominio en el
mismo proceso. Por eso "cuándo consulta" describe el ciclo del componente.

| Flujo/ruta UI | Contrato canónico | Cuándo consulta | Auth | Estado asíncrono | Éxito/actualización | Errores→UX | Prueba |
|---|---|---|---|---|---|---|---|
| Iniciar sesión /login | docs/contratos/usuarios.md — `POST /login`, v1 | al enviar el formulario | ninguna | enviando → éxito/error | redirección a /panel | CREDENCIALES_INVALIDAS → mensaje único junto al formulario | Feature: login válido, inválido y usuario inactivo |
| Tablero /panel | docs/contratos/inventario.md — `GET /panel`, v1 | al montar el componente | administrador, vendedor | carga → éxito/error | dos listados renderizados | error → aviso con opción de reintentar | Feature: alertas por vencer, vencidos y stock bajo |
| Venta /ventas/nueva | docs/contratos/ventas.md — `POST /ventas`, v1 | al confirmar la venta | administrador, vendedor | confirmando → éxito/error, con el botón bloqueado mientras dure | limpia la venta y ofrece imprimir el comprobante | STOCK_INSUFICIENTE → aviso con la cantidad disponible; FACTURA_REQUIERE_RUC y BOLETA_REQUIERE_DOCUMENTO → aviso junto al cliente | Feature: FEFO con varios lotes, stock insuficiente, lote vencido, doble envío con la misma clave |
| Búsqueda en la caja /ventas/nueva | docs/contratos/productos.md — `GET /productos`, v1 | al escribir, con espera de 300 ms desde la última tecla | administrador, vendedor | buscando → resultados/vacío | agrega la línea seleccionada | sin resultados → aviso que no borra lo escrito | Feature: búsqueda por código exacto y por nombre parcial |
| Listado de ventas /ventas | docs/contratos/ventas.md — `GET /ventas`, v1 | al montar y al cambiar filtros o página | administrador, vendedor | carga → éxito/error | reemplaza la lista conservando los filtros | NO_AUTORIZADO → redirección al panel con aviso | Feature: el vendedor solo ve las propias, incluido el total del paginado |
| Comprobantes /comprobantes | docs/contratos/comprobantes.md — `GET /comprobantes` y `POST /comprobantes/{id}/reenviar`, v1 | al montar, al cambiar filtros, y al reenviar | administrador | carga → éxito/error; reenviando → fila actualizada | actualiza solo la fila afectada | COMPROBANTE_NO_REENVIABLE → aviso en la fila; SUNAT_RECHAZO → motivo visible en la fila | Feature: reenvío de pendiente, rechazo de reenvío sobre aceptado |
| Resúmenes /resumenes-diarios | docs/contratos/comprobantes.md — `GET /resumenes-diarios` y `POST /resumenes-diarios`, v1 | al montar y al enviar manualmente | administrador | enviando → fila actualizada | actualiza la fila | BOLETA_YA_RESUMIDA → aviso explicando que ese día ya fue informado | Feature: envío manual y día ya resumido |
| Compra /compras/nueva | docs/contratos/compras.md — `POST /compras`, v1 | al confirmar | administrador | confirmando → éxito/error | navega al detalle de la compra | LOTE_VENCIMIENTO_PASADO → error junto a la fecha de la línea; COMPRA_DOCUMENTO_DUPLICADO → aviso junto al número | Feature: compra que crea lote nuevo, compra que acumula en lote existente, documento duplicado |
| Inventario /inventario | docs/contratos/inventario.md — `GET /inventario`, v1 | al montar y al cambiar filtros o página | administrador, vendedor | carga → éxito/error | reemplaza la lista conservando filtros | error → aviso con reintento | Feature: orden por vencimiento y exclusión de vencidos del disponible |
| Kardex /inventario/kardex | docs/contratos/inventario.md — `GET /inventario/kardex`, v1 | al elegir producto y al cambiar el rango | administrador | carga → éxito/error | reemplaza la lista | CAMPO_FUERA_DE_RANGO → error junto al rango de fechas | Feature: rango válido, rango invertido, rango mayor al máximo |
| Ajuste /inventario/ajustes | docs/contratos/inventario.md — `POST /inventario/ajustes`, v1 | al confirmar | administrador | confirmando → éxito/error | vuelve al inventario actualizado | AJUSTE_SIN_MOTIVO y AJUSTE_CANTIDAD_NEGATIVA → error junto al campo | Feature: ajuste hacia arriba, hacia abajo y a cantidad negativa |
| Catálogo /productos, /categorias | docs/contratos/productos.md — v1 | al montar y al guardar | administrador | guardando → éxito/error | actualiza la fila sin recargar la lista | PRODUCTO_PRECIO_MAYOR_INVALIDO → error junto al precio mayor; PRODUCTO_CODIGO_DUPLICADO → junto al código; CATEGORIA_NOMBRE_DUPLICADO → junto al nombre de la categoría; CAMPO_FORMATO_INVALIDO → junto al campo que `detalle` nombre | Feature: alta, edición, precio mayor inválido, código duplicado |
| Clientes /clientes | docs/contratos/clientes.md — v1 | al montar, al guardar, y desde la venta | administrador, vendedor | guardando → éxito/error | creado desde la venta, lo devuelve seleccionado | CAMPO_REQUERIDO, DOCUMENTO_INVALIDO y DOCUMENTO_DUPLICADO → error junto al número, según el tipo elegido | Feature: DNI, RUC, sin documento, documento duplicado |
| Proveedores /proveedores | docs/contratos/proveedores.md — v1 | al montar y al guardar | administrador | guardando → éxito/error | actualiza la fila | CAMPO_REQUERIDO, DOCUMENTO_INVALIDO y DOCUMENTO_DUPLICADO → error junto al número | Feature: alta, RUC inválido, duplicado |
| Usuarios /usuarios | docs/contratos/usuarios.md — v1 | al montar y al guardar | administrador | guardando → éxito/error | actualiza la fila | DOCUMENTO_DUPLICADO → error junto al **correo**, no junto a un documento: el código está mal nombrado y se conserva (ver la nota al pie en `docs/contratos/servicios-de-dominio.md`) | Feature: alta, cambio de rol, autodesactivación rechazada |
| Reportes /reportes/* | docs/contratos/ventas.md — `GET /reportes/ventas` y `GET /reportes/utilidad`, v1 | al montar con el rango de la URL, y al cambiarlo | administrador | carga → éxito/error | reemplaza el reporte y actualiza la URL | CAMPO_FUERA_DE_RANGO → error junto al rango | Feature: totales que cuadran, utilidad con costo por lote |

## Convenciones compartidas

- **Estado en la URL**: los filtros, el rango de fechas y la página viven en la URL, para que recargar, compartir el enlace y usar atrás/adelante funcionen igual.
- **Protección contra doble envío**: todo botón que confirma una escritura se bloquea mientras la operación está en curso. En la venta eso se complementa con la clave de idempotencia declarada en su contrato, porque bloquear el botón no protege de una recarga.
- **Componentes reutilizables**: encabezado, menú lateral, tabla, formulario y aviso viven una sola vez en `resources/views/components/` y se reutilizan; no se copia el HTML de una vista a otra.
- **Variables de entorno que la interfaz usa**: ninguna propia. La interfaz no habla con servicios externos; nunca se expone una credencial al navegador.
- **Sin caché de datos en el cliente**: cada componente consulta al servidor cuando corresponde. No hay estado global duplicado que invalidar.

Estado: aprobado
Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
