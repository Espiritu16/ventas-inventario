# Requisitos no funcionales — ventas-inventario

Todo RNF nace en estado `propuesto` y solo pasa a `aprobado` cuando el usuario
real aprueba su contenido exacto.

---

## RNF-001 — rendimiento: agilidad de la caja
- Requisito: agregar una línea a la venta responde en menos de 1 s, y confirmar la venta (descuento FEFO, kardex, correlativo y comprobante) responde en menos de 2 s en el percentil 95, sin incluir el envío a SUNAT.
- Cómo se mide/verifica: prueba automatizada que registra una venta de 10 líneas sobre una base con 5 000 productos y 20 000 lotes, midiendo el tiempo de la confirmación.
- Aplica a: pantalla de venta y su confirmación.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-002 — disponibilidad: la venta no depende de SUNAT
- Requisito: la confirmación de una venta nunca queda condicionada a la respuesta de SUNAT. Una falla, lentitud o indisponibilidad del servicio de SUNAT no impide registrar la venta ni entregar el comprobante impreso.
- Cómo se mide/verifica: prueba de integración con el servicio de SUNAT simulado como no disponible y con latencia alta; la venta debe completarse igual y el comprobante quedar `PENDIENTE`.
- Aplica a: venta y emisión de comprobantes.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-003 — integridad: consistencia entre venta, stock y comprobante
- Requisito: el registro de una venta, el descuento de sus lotes, los movimientos de kardex y la reserva del correlativo ocurren de forma atómica: o quedan todos, o no queda ninguno. Ningún lote puede quedar con cantidad negativa y ningún correlativo puede repetirse.
- Cómo se mide/verifica: pruebas de concurrencia que confirman dos ventas simultáneas sobre el mismo lote y sobre la misma serie, verificando que no haya cantidad negativa ni correlativo duplicado; restricciones a nivel de base de datos que rechazan ambos casos.
- Aplica a: ventas, inventario y comprobantes.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-004 — trazabilidad: registro de operaciones sensibles
- Requisito: todo ajuste de inventario, cambio de precio, anulación de operación y envío a SUNAT queda registrado con usuario, fecha y hora, y valor anterior cuando aplique. El kardex es inmutable: no se edita ni se borra.
- Cómo se mide/verifica: prueba que confirma que un ajuste y un cambio de precio dejan registro con su responsable; revisión de que no existe operación de edición ni borrado sobre movimientos de kardex.
- Aplica a: inventario, catálogo y comprobantes.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-005 — seguridad: custodia del certificado digital
- Requisito: el certificado digital tributario y su clave nunca se versionan en el repositorio, ni aparecen en documentación, logs o mensajes de error. Se cargan desde una ubicación configurada por variable de entorno. El sistema avisa con al menos 30 días de anticipación al vencimiento del certificado.
- Cómo se mide/verifica: revisión de que `.gitignore` excluye la ruta de certificados y de que ningún log los registra; prueba que confirma la alerta de vencimiento próximo.
- Aplica a: emisión electrónica.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-006 — datos: fecha, hora y moneda
- Requisito: las fechas y horas se almacenan en UTC y se muestran en la zona horaria de Lima (UTC-5). Los importes se manejan con precisión decimal exacta, nunca con números de punto flotante, y se redondean a 2 decimales siguiendo la regla de redondeo que exige SUNAT en el comprobante.
- Cómo se mide/verifica: prueba que confirma que el total de una venta con importes de 3 decimales coincide con el declarado en el XML; revisión del tipo de dato usado en persistencia.
- Aplica a: todo el sistema.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-007 — respaldo: recuperación de la base de datos
- Requisito: existe una copia de respaldo diaria automática de la base de datos, con retención mínima de 30 días, y un procedimiento probado de restauración.
- Cómo se mide/verifica: verificación de que la copia se genera y de que una restauración sobre una base vacía reproduce los datos.
- Aplica a: despliegue y operación.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-008 — usabilidad: operación de la caja con teclado
- Requisito: la pantalla de venta se opera completa con teclado —búsqueda de producto, cantidad, tipo de precio y confirmación— sin obligar al uso del mouse, y admite lectura por código de barras como entrada de búsqueda.
- Cómo se mide/verifica: recorrido manual documentado que registra una venta completa sin usar el mouse.
- Aplica a: pantalla de venta.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-010 — seguridad: validación de input
- Requisito: todo input aplica la derivación completa de validaciones de backend: inventariar campos, rechazar formato o tipo inválido de forma explícita, nunca corregirlo en silencio para "limpiarlo".
- Cómo se mide/verifica: prueba que envía input malformado y espera el resultado de validación documentado (422), no una versión corregida.
- Aplica a: toda entrada que recibe el backend — formularios, componentes Livewire, comandos de consola y jobs.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-011 — seguridad: queries parametrizadas
- Requisito: ninguna consulta se arma por concatenación o interpolación de cadenas con input del usuario.
- Cómo se mide/verifica: revisión de código sobre la capa de persistencia más prueba de inyección.
- Aplica a: toda la capa de persistencia.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-012 — seguridad: codificación de salida contextual
- Requisito: la codificación que previene XSS ocurre en el punto donde el dato se renderiza en la vista Blade, según el contexto (HTML, atributo, JS, URL).
- Cómo se mide/verifica: prueba que envía un payload XSS típico en un nombre de producto o cliente y confirma que sale escapado en el HTML renderizado, no ejecutado.
- Aplica a: capa de vistas Blade y componentes Livewire.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-013 — seguridad: control de acceso deny-by-default
- Requisito: una ruta, comando o job sin regla de autorización explícita en `docs/requisitos/actores-permisos.md` rechaza, no permite.
- Cómo se mide/verifica: por cada recurso, pruebas sin autenticar, con credencial válida y con credencial inválida o expirada; más una prueba por cada rol con fila propia y por cada condición de alcance, en su rama cumplida y no cumplida.
- Aplica a: toda ruta, comando y job del sistema.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19

## RNF-014 — seguridad: sin secretos expuestos
- Requisito: ningún log, respuesta de error o pantalla expone credenciales, la clave del certificado, ni datos personales de clientes más allá de lo que el comprobante exige.
- Cómo se mide/verifica: revisión de logs y respuestas de error en la auditoría de cierre de cada sprint.
- Aplica a: todo el sistema.
- Estado: aprobado — Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19
