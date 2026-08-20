# Manejo de errores — ventas-inventario

Autoridad: Arquitectura. El sistema entrega su interfaz por HTTP (rutas web y
componentes Livewire), así que la columna de resultado usa códigos HTTP. Los
procesos en segundo plano (envío a SUNAT, resumen diario) no devuelven HTTP:
su resultado se registra en el estado del comprobante y en `LogError`, usando
los mismos códigos de la taxonomía.

## Formato estándar de error

```json
{ "error": { "codigo": "string", "mensaje": "string", "detalle": {} } }
```

- `codigo`: valor estable de la taxonomía. Nunca se traduce ni se renombra sin subir versión del contrato.
- `mensaje`: texto en español, dirigido a la persona que opera el sistema, sin jerga técnica ni datos internos.
- `detalle`: opcional, con información accionable (por ejemplo, la cantidad disponible cuando el stock no alcanza). Nunca incluye credenciales, rutas internas ni trazas.

En las pantallas Livewire el mismo error se muestra como mensaje junto al
campo o como aviso de la operación; el código es el que se registra y el que
las pruebas verifican, no el texto visible.

## Taxonomía

### Acceso

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| NO_AUTENTICADO | 401 | no hay sesión activa, o la sesión expiró o es inválida |
| NO_AUTORIZADO | 403 | hay sesión válida, pero el rol no tiene permiso para esa operación (ver docs/requisitos/actores-permisos.md) |
| CREDENCIALES_INVALIDAS | 422 | usuario o contraseña incorrectos; no revela cuál de los dos falló |

### Validación general

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| CAMPO_REQUERIDO | 422 | falta un campo obligatorio, o llega vacío o solo con espacios |
| CAMPO_FORMATO_INVALIDO | 422 | el valor no cumple el formato declarado en el contrato |
| CAMPO_FUERA_DE_RANGO | 422 | el valor está fuera del mínimo/máximo o de la precisión declarada |
| RECURSO_NO_ENCONTRADO | 404 | el identificador no existe o corresponde a un registro inactivo no accesible |

### Catálogo y maestros

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| CATEGORIA_NOMBRE_DUPLICADO | 409 | ya existe una categoría con ese nombre, comparado sin distinguir mayúsculas |
| PRODUCTO_CODIGO_DUPLICADO | 409 | ya existe un producto con ese código interno |
| PRODUCTO_PRECIO_MAYOR_INVALIDO | 422 | el precio al por mayor es mayor que el precio al por menor |
| PRODUCTO_INACTIVO | 422 | se intenta comprar o vender un producto desactivado |
| DOCUMENTO_DUPLICADO | 409 | ya existe un cliente o proveedor con ese tipo y número de documento |
| DOCUMENTO_INVALIDO | 422 | el número de documento no cumple la longitud o el formato de su tipo |

### Compras e inventario

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| COMPRA_SIN_LINEAS | 422 | se intenta confirmar una compra sin ninguna línea |
| COMPRA_DOCUMENTO_DUPLICADO | 409 | ya se registró ese documento para ese proveedor |
| LOTE_VENCIMIENTO_PASADO | 422 | se intenta ingresar mercadería con fecha de vencimiento anterior a hoy |
| AJUSTE_SIN_MOTIVO | 422 | se intenta ajustar un lote sin indicar motivo |
| AJUSTE_CANTIDAD_NEGATIVA | 422 | el ajuste dejaría el lote con cantidad menor a cero |

### Ventas

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| VENTA_SIN_LINEAS | 422 | se intenta confirmar una venta sin ninguna línea |
| STOCK_INSUFICIENTE | 409 | el stock disponible no vencido no alcanza para la cantidad pedida; `detalle` informa la cantidad disponible |
| LOTE_VENCIDO | 409 | el único lote con existencia para cubrir la línea está vencido |
| FACTURA_REQUIERE_RUC | 422 | se elige factura y el cliente no tiene RUC |
| BOLETA_REQUIERE_DOCUMENTO | 422 | el importe de la boleta supera el tope permitido para venta sin documento del cliente |
| TIPO_PRECIO_INVALIDO | 422 | el tipo de precio de una línea no es `menor` ni `mayor` |

### Comprobantes y SUNAT

| Código | HTTP status | Cuándo se usa |
|---|---|---|
| SERIE_NO_CONFIGURADA | 422 | no existe una serie activa para el tipo de comprobante solicitado |
| CORRELATIVO_EN_CONFLICTO | 409 | no se pudo reservar el correlativo por concurrencia; la operación se reintenta y solo se informa si el reintento también falla |
| TRANSICION_COMPROBANTE_INVALIDA | 409 | se intenta un cambio de estado no permitido por el ciclo de vida de RF-013 |
| COMPROBANTE_NO_REENVIABLE | 409 | se intenta reenviar un comprobante que no está en estado `PENDIENTE` |
| SUNAT_NO_DISPONIBLE | 503 | el servicio de SUNAT no respondió o falló por causa transitoria; el comprobante permanece `PENDIENTE` y se reintenta |
| SUNAT_RECHAZO | 422 | SUNAT rechazó el comprobante por sus datos; `detalle` conserva el código y el mensaje exactos devueltos por SUNAT y no se reintenta |
| CERTIFICADO_NO_DISPONIBLE | 500 | no se pudo cargar el certificado digital o su clave desde la ubicación configurada |
| CERTIFICADO_VENCIDO | 500 | el certificado digital está vencido; ningún comprobante puede firmarse |
| BOLETA_YA_RESUMIDA | 409 | se intenta incluir en un resumen diario una boleta ya incluida en un resumen aceptado |

### Reglas transversales

- Un error nunca deja una operación a medias: toda operación que escribe (venta, compra, ajuste) es atómica, y si termina en error no deja rastro parcial en inventario, kardex ni correlativos.
- `SUNAT_NO_DISPONIBLE` y `SUNAT_RECHAZO` son categóricamente distintos y se tratan distinto: el primero se reintenta automáticamente, el segundo nunca.
- Todo error de severidad `error` o `critical` se registra en `LogError` con su `trace_id`, y además se escribe a la salida estándar del proceso.
- **Un código de unicidad se nombra `ENTIDAD_CAMPO_DUPLICADO`, con la entidad adelante.** No es estética: un código que nombra solo el campo se puede aplicar a cualquier entidad que tenga algo parecido, y eso ya pasó. `DOCUMENTO_DUPLICADO` es el único de los cuatro que omite la entidad, y es exactamente el que se usó fuera de su significado —para un nombre de categoría, que no es un documento—. Se conserva porque en clientes y proveedores es correcto, pero **ningún código nuevo se nombra así**. Lo observó `implementation-backend` al corregir ese uso: fue a ver si quedaba un tercer caso y encontró que el patrón predice cuáles atraen usos indebidos.
- **Un error de campo nombra su campo en `detalle`.** Las pantallas muestran el mensaje junto al campo que lo produjo, y para saber cuál es leen ese dato. Sin él, un error de campo se degrada a aviso general de la operación. La correspondencia código→campo **depende del servicio** —`DOCUMENTO_DUPLICADO` es `numero_documento` en clientes y era `nombre` en categorías— así que no puede deducirse desde la pantalla sin crear un segundo lugar donde vive esa equivalencia.

- **Un fallo de unicidad no tiene código por defecto: lo declara quien valida.** `ValidadorDeDominio` traduce reglas de validación a códigos, y su tabla mapeaba `'Unique'` a `DOCUMENTO_DUPLICADO` para cualquier campo de cualquier entidad — es decir, el código mal nombrado era además el que se aplicaba solo, sin que nadie lo eligiera. Un validador compartido **no sabe de qué entidad es el campo**, así que no puede nombrar el código correcto y no debe intentarlo: la regla `ENTIDAD_CAMPO_DUPLICADO` solo es satisfacible desde el servicio, que sí lo sabe. La entrada `'Unique'` se retira de la tabla por defecto y el código se pasa por campo en `$codigosPorCampo`, que es el mecanismo que el propio validador ya ofrece y que catálogo y compras ya usan. Un `unique` sin código declarado deja de resolverse en silencio.
  Lo planteó `implementation-frontend` al mapear errores a campos en sus pantallas. Al verificarlo apareció además que `UsuarioService` tiene **su propia copia privada** de esa traducción, escrita en S-01-B antes de que existiera el validador compartido, y que las dos ya divergieron: la compartida trata `Between` y la copia no. Es la séptima instancia del patrón de dos fuentes mantenidas a mano, y la primera dentro del código de producción y no entre código y documento.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, ejercida por el agente sobre el diseño aprobado por Kevin Espíritu — fecha: 2026-08-19
