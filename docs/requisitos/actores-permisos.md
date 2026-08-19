# Actores y permisos — ventas-inventario

Autoridad: Arquitectura. Toda combinación actor × recurso × acción que no
aparezca en la matriz con su condición cumplida se trata como **denegada**.

## Actor: Administrador
- Descripción: dueño o encargado del negocio. Configura el sistema, compra mercadería, corrige inventario, emite y vigila comprobantes, y consulta reportes.
- Rol técnico: `administrador` (campo `rol` de la entidad `Usuario`)
- Deriva de: RF-002

## Actor: Vendedor
- Descripción: persona que atiende la caja. Registra ventas y consulta catálogo y stock.
- Rol técnico: `vendedor` (campo `rol` de la entidad `Usuario`)
- Deriva de: RF-002

## Actor: Anónimo
- Descripción: solicitud sin sesión activa.
- Rol técnico: (ninguno — ausencia de credencial)
- Deriva de: RF-001

## Actor: Sistema
- Descripción: procesos automáticos del propio sistema — el trabajo en segundo plano que envía comprobantes a SUNAT y el que envía el resumen diario. No es una persona y no tiene sesión.
- Rol técnico: `sistema` (contexto de ejecución de job/comando, no una sesión de usuario)
- Deriva de: no deriva de RF, es un actor técnico

## Rutas de infraestructura

Rutas que no exponen ningún recurso de negocio y por lo tanto no se modelan con un
actor. Se declaran aquí **explícitamente** porque el control de acceso es
deny-by-default (RNF-013): una ruta sin declaración se rechaza, y estas dos tienen
que responder. Declararlas no es una excepción al mecanismo — es usarlo como
corresponde, dejando por escrito lo que de otro modo sería una omisión silenciosa.

| Ruta | Tratamiento | Por qué | Deriva de |
|---|---|---|---|
| `GET /up` | Pública, sin sesión y sin rol | Verificación de salud del propio framework. La consumen la orquestación de contenedores de S-DO-01 y el despliegue de S-DO-02, que no tienen sesión ni pueden tenerla. No expone datos: responde vivo o no vivo | RNF-002, S-DO-01, S-DO-02 |
| `GET /` | Pública, redirige | Sin sesión redirige a `/login`; con sesión activa, a `/panel`. No entrega contenido propio, así que no hay nada que autorizar: la protección real vive en el destino | RF-001 |

Decidido por Arquitectura el 2026-08-19, a raíz del desajuste que reportó
`implementation-backend` al implementar UT-04 de S-01-B. Ninguna otra ruta puede
tratarse así sin agregarse a esta tabla: la lista es cerrada, no un criterio general
de "lo que parezca infraestructura".

## Matriz de permisos

| Actor | Rol técnico | Recurso/Operación | Acción | Condición/alcance | Permitido | Deriva de |
|---|---|---|---|---|---|---|
| Anónimo | (ninguno) | POST /login | autenticar | — | Sí | RF-001 |
| Administrador | administrador | POST /logout | cerrar sesión | — | Sí | RF-001 |
| Vendedor | vendedor | POST /logout | cerrar sesión | — | Sí | RF-001 |
| Administrador | administrador | GET /panel | ver tablero con alertas | — | Sí | RF-018, RF-019 |
| Vendedor | vendedor | GET /panel | ver tablero con alertas | solo alertas de vencimiento y stock; sin indicadores de utilidad | Sí | RF-018, RF-019 |
| Administrador | administrador | GET /usuarios | listar | — | Sí | RF-002 |
| Administrador | administrador | POST /usuarios | crear | — | Sí | RF-002 |
| Administrador | administrador | PATCH /usuarios/{id} | actualizar | — | Sí | RF-002 |
| Vendedor | vendedor | GET /usuarios | listar | — | No | RF-002 |
| Vendedor | vendedor | POST /usuarios | crear | — | No | RF-002 |
| Administrador | administrador | GET /categorias | listar | — | Sí | RF-003 |
| Vendedor | vendedor | GET /categorias | listar | — | Sí | RF-003 |
| Administrador | administrador | POST /categorias | crear | — | Sí | RF-003 |
| Administrador | administrador | PATCH /categorias/{id} | actualizar | — | Sí | RF-003 |
| Vendedor | vendedor | POST /categorias | crear | — | No | RF-003 |
| Administrador | administrador | GET /productos | listar | — | Sí | RF-004 |
| Vendedor | vendedor | GET /productos | listar | sin columna de costo ni de margen | Sí | RF-004, RF-011 |
| Administrador | administrador | GET /productos/{id} | ver | — | Sí | RF-004 |
| Vendedor | vendedor | GET /productos/{id} | ver | sin costo ni margen | Sí | RF-004 |
| Administrador | administrador | POST /productos | crear | — | Sí | RF-004 |
| Administrador | administrador | PATCH /productos/{id} | actualizar | — | Sí | RF-004 |
| Vendedor | vendedor | POST /productos | crear | — | No | RF-004 |
| Vendedor | vendedor | PATCH /productos/{id} | actualizar | — | No | RF-004 |
| Administrador | administrador | GET /proveedores | listar | — | Sí | RF-005 |
| Administrador | administrador | POST /proveedores | crear | — | Sí | RF-005 |
| Administrador | administrador | PATCH /proveedores/{id} | actualizar | — | Sí | RF-005 |
| Vendedor | vendedor | GET /proveedores | listar | — | No | RF-005 |
| Administrador | administrador | GET /compras | listar | — | Sí | RF-006 |
| Administrador | administrador | GET /compras/{id} | ver | — | Sí | RF-006 |
| Administrador | administrador | POST /compras | crear | — | Sí | RF-006 |
| Vendedor | vendedor | GET /compras | listar | — | No | RF-006 |
| Vendedor | vendedor | POST /compras | crear | — | No | RF-006 |
| Administrador | administrador | GET /inventario | consultar stock y lotes | — | Sí | RF-007 |
| Vendedor | vendedor | GET /inventario | consultar stock y lotes | sin costo unitario del lote | Sí | RF-007 |
| Administrador | administrador | GET /inventario/kardex | consultar movimientos | — | Sí | RF-008 |
| Vendedor | vendedor | GET /inventario/kardex | consultar movimientos | — | No | RF-008 |
| Administrador | administrador | POST /inventario/ajustes | ajustar lote | — | Sí | RF-009 |
| Vendedor | vendedor | POST /inventario/ajustes | ajustar lote | — | No | RF-009 |
| Administrador | administrador | GET /clientes | listar | — | Sí | RF-010 |
| Vendedor | vendedor | GET /clientes | listar | — | Sí | RF-010 |
| Administrador | administrador | POST /clientes | crear | — | Sí | RF-010 |
| Vendedor | vendedor | POST /clientes | crear | — | Sí | RF-010 |
| Administrador | administrador | PATCH /clientes/{id} | actualizar | — | Sí | RF-010 |
| Vendedor | vendedor | PATCH /clientes/{id} | actualizar | — | No | RF-010 |
| Administrador | administrador | POST /ventas | registrar venta | — | Sí | RF-011, RF-012 |
| Vendedor | vendedor | POST /ventas | registrar venta | — | Sí | RF-011, RF-012 |
| Administrador | administrador | GET /ventas | listar | — | Sí | RF-020 |
| Vendedor | vendedor | GET /ventas | listar | solo las registradas por el propio usuario (`venta.usuario_id == actor.id`) | Sí | RF-011 |
| Administrador | administrador | GET /ventas/{id} | ver | — | Sí | RF-020 |
| Vendedor | vendedor | GET /ventas/{id} | ver | solo las propias (`venta.usuario_id == actor.id`) | Sí | RF-011 |
| Administrador | administrador | GET /series-comprobante | listar | — | Sí | RF-014 |
| Administrador | administrador | POST /series-comprobante | crear | — | Sí | RF-014 |
| Vendedor | vendedor | POST /series-comprobante | crear | — | No | RF-014 |
| Administrador | administrador | GET /comprobantes | listar y filtrar por estado | — | Sí | RF-016 |
| Vendedor | vendedor | GET /comprobantes | listar y filtrar por estado | — | No | RF-016 |
| Administrador | administrador | POST /comprobantes/{id}/reenviar | reenviar a SUNAT | solo si el comprobante está en estado `PENDIENTE` | Sí | RF-016 |
| Vendedor | vendedor | POST /comprobantes/{id}/reenviar | reenviar a SUNAT | — | No | RF-016 |
| Sistema | sistema | job: enviar-comprobante | enviar a SUNAT y registrar resultado | — | Sí | RF-015 |
| Sistema | sistema | job: enviar-resumen-diario | enviar resumen de boletas | — | Sí | RF-017 |
| Administrador | administrador | GET /resumenes-diarios | listar | — | Sí | RF-017 |
| Administrador | administrador | POST /resumenes-diarios | disparar envío manual | — | Sí | RF-017 |
| Vendedor | vendedor | GET /resumenes-diarios | listar | — | No | RF-017 |
| Administrador | administrador | GET /reportes/ventas | consultar | — | Sí | RF-020 |
| Vendedor | vendedor | GET /reportes/ventas | consultar | — | No | RF-020 |
| Administrador | administrador | GET /reportes/utilidad | consultar | — | Sí | RF-021 |
| Vendedor | vendedor | GET /reportes/utilidad | consultar | — | No | RF-021 |

Nota sobre las condiciones de alcance: cuando una fila restringe columnas
visibles (costo, margen), la operación **sí** está permitida; lo que se acota
es la proyección de campos, que se documenta en el contrato del recurso, no
como una denegación de la operación completa.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, ejercida por el agente sobre el diseño aprobado por Kevin Espíritu — fecha: 2026-08-19
