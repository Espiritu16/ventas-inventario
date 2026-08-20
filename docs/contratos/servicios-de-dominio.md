# Contrato — Servicios de dominio

> **Sincronizado con el código el 2026-08-20.** Este documento se escribió antes del primer
> sprint y **nadie lo actualizó al implementar**. Al ir a consumirlo desde S-02-F apareció que
> declaraba métodos que no existen —`ver()`, `consultarStock()`, `consultarKardex()`,
> `lotesPorVencer()`, `productosBajoMinimo()`, `buscar()`— y omitía cuatro servicios enteros.
>
> Nadie lo detectó antes porque **hasta ahora ningún sprint de frontend había consumido un
> servicio**: QA validó cinco sprints contra sus RFC y sus contratos por dominio, y este
> documento no entraba en ninguna de esas comparaciones. Es la sexta instancia del patrón de
> dos fuentes mantenidas a mano, y la más grave: es la interfaz completa entre los dos frentes.
>
> Se sincroniza hacia el código, no al revés: la implementación está validada por QA en cinco
> sprints y los nombres reales son coherentes entre sí. **Pendiente asignado a S-02-F: una
> prueba que compare este documento con los métodos públicos reales**, como la que ya existe
> para la matriz de permisos. Sin ella vuelve a divergir en el próximo sprint.


Autoridad: Arquitectura. Versión del contrato: v1.

**Este es el contrato entre backend y frontend.** Con Livewire no hay una API
HTTP entre ambos: los componentes de la interfaz invocan directamente los
servicios de dominio en el mismo proceso. Por eso lo que hace posible el trabajo
en paralelo no es una ruta, sino la firma de estos métodos.

Reglas que lo gobiernan:

- **`implementation-backend` es el dueño**: implementa estos servicios y puede proponer cambios de firma, que Arquitectura aprueba.
- **`implementation-frontend` los consume**: los invoca desde sus componentes y **no cambia una firma**. Si necesita una distinta, escala a Arquitectura y su sprint queda bloqueado hasta la aprobación.
- Cambiar el nombre de un método, sus parámetros, su retorno o los errores que produce es un cambio **incompatible**: sube la versión de este contrato y reabre los RFC que lo consumen.
- Agregar un método nuevo, o un parámetro opcional al final, no es incompatible.
- Los códigos de error son los de `docs/errores/manejo-errores.md`. Un servicio **nunca corrige la entrada en silencio**: rechaza con su código.
- Toda operación que escribe es atómica: o se aplica completa, o no deja rastro.

Cada servicio vive en `app/Dominios/<Dominio>/`, según `laravel-estructura`.

---

## Cómo se lee este documento

Cada fila declara **una firma real**, copiada del código, no una descripción de ella. La
columna **Estado** dice si ese método existe hoy:

| Estado | Significa |
|---|---|
| `implementado` | el método existe en el código, con esa firma exacta |
| `pendiente S-XX-B` | está aprobado y asignado a ese sprint; todavía no existe |
| `sin sprint` | deriva de un RF aprobado y **ningún sprint lo implementa**. Es un hueco, no un plan |

La prueba de consistencia asignada a S-02-F compara este documento contra los métodos
públicos reales **en las dos direcciones**: un método sin declarar y una declaración
`implementado` sin método son ambos falla. Las filas `pendiente` y `sin sprint` quedan fuera
de la comparación, que es justamente lo que las obliga a estar marcadas: una fila mal marcada
como `pendiente` esconde un método que sí existe.

Por eso la primera columna es un bloque de código con la firma completa y nada más. Si mañana
alguien la reescribe en prosa, la prueba se vuelve frágil y se pone roja sin que nada esté mal.

**Cómo reconoce la prueba una tabla de servicios: por su encabezado, no por su forma.** Busca
exactamente la fila `| Método | Entrada | Devuelve | Errores | Estado | Deriva de |` y lee las
filas que vienen debajo hasta la línea en blanco. La tabla de arriba —la que explica los
valores de `Estado`— queda fuera **por lo que es**, no por cuántas columnas tiene.

La diferencia no es de estilo. Contar columnas funciona hoy y se rompe el día que una tabla de
servicios gane una, sin que nada lo anuncie y **en la dirección peligrosa**: una tabla que deja
de ser reconocida desaparece de la comparación en silencio, y sus métodos pasan a estar sin
verificar mientras la prueba sigue en verde.

Por eso la prueba además **afirma cuántas tablas de servicio encontró**. Si un día son cinco en
vez de las que hay, se pone roja por la razón correcta en lugar de comparar menos cosas sin
decirlo. Es la misma exigencia que este proyecto ya le hace a la sonda de concurrencia: que el
instrumento verifique que midió lo que cree haber medido. Lo observó `qa`, corrigiendo un
criterio que yo había dado por suficiente.

## Los tres códigos genéricos no se enumeran por fila

**Todo método que recibe datos puede rechazar con `CAMPO_REQUERIDO`, `CAMPO_FORMATO_INVALIDO`
o `CAMPO_FUERA_DE_RANGO`, y su `detalle` nombra el campo.** No hace falta buscarlos en la fila:
si el método valida entrada, los tres están disponibles. La columna **Errores** enumera solo
los códigos **propios** de esa operación — los que la pantalla tiene que tratar de forma
distinta.

Esto se decidió el 2026-08-20, después de que `implementation-frontend` encontrara que la
columna tenía dos huecos **sistemáticos**: `CAMPO_FUERA_DE_RANGO` no estaba declarado en
ninguna fila de catálogo, proveedores ni clientes, y los cuatro servicios lo producen —todos
usan reglas `between:`, que `ValidadorDeDominio` traduce a ese código—; y
`CAMPO_FORMATO_INVALIDO` faltaba en los `actualizar` que rechazan cambiar el documento. Al
verificarlo apareció que era peor: `CompraService` también usa `between:` tres veces, y
`VentaService` y `ConsultaDeInventarioService` lanzan `CAMPO_FUERA_DE_RANGO` directo.

Esos huecos sobrevivieron a una sincronización hecha a conciencia unas horas antes, y la razón
es la que importa: **la prueba de consistencia compara firmas, y estos códigos no están en la
firma.** Ir fila por fila agregando los tres códigos cerraría el estado de hoy y garantizaría
que vuelvan a faltar en el próximo método que alguien escriba. Es la cuarta vez que este
proyecto elige declarar el principio en vez de enumerar los casos, como los assets de Livewire
por patrón, los permisos por área y ADR-0006.

### Cómo se cierra la columna Errores — observando, no deduciendo

**Estado: diseño aprobado por Arquitectura el 2026-08-20. Sin sprint asignado; propuesto para
S-02-F junto a la prueba de consistencia de firmas, pendiente de la decisión del usuario.**

Deducir qué códigos puede lanzar un método obliga a seguir las llamadas y adivinar, y este
proyecto ya rechazó esa fragilidad al elegir preguntarle el nombre de la base al motor en vez
de deducirlo de la configuración. La salida es la de siempre: **observar lo que de verdad
pasa.**

Mientras corre la suite —que ya ejercita los servicios y ya provoca la mayoría de estos errores
a propósito— cada `ErrorDeDominio` que escape de un método de servicio se anota como el par
`(método, código)`. El error ya lleva su código y el método sale del punto donde se lanza. Al
terminar, se compara el conjunto observado contra el declarado acá. **No sigue llamadas ni
adivina: registra lo que ocurrió.**

Da dos señales, y tratarlas igual arruinaría las dos:

| Señal | Qué significa | Qué hace |
|---|---|---|
| Código **observado y no declarado** | un consumidor recibe algo que el contrato no anuncia — es el hueco que apareció hoy | **falla** |
| Código **declarado y nunca observado** | o sobra la declaración, o falta una prueba que provoque ese error | **avisa, no falla** |

Convertir la segunda en fallo la haría insoportable; convertirla en silencio la haría inútil.

**El límite, dicho de frente: solo ve lo que la suite ejercita.** Un código que ninguna prueba
dispara no aparece. Pero eso cambia la naturaleza del problema — pasa de *"no se puede saber
sin adivinar"* a *"se sabe exactamente lo que las pruebas cubren, y lo que no está cubierto es
visible"*. Un hueco de cobertura es medible y accionable; un hueco de adivinación no lo es. La
segunda señal es la que lo vuelve explícito.

Dos condiciones al implementarlo, las dos de `qa`, que propuso el mecanismo entero:

- El registro **observa y relanza**. Si se traga el error, las pruebas dejan de fallar donde
  deben.
- El instrumento **afirma que observó algo**. Una corrida que termina con cero pares
  registrados no significa "no hay códigos sin declarar": significa que el registro no se
  enganchó. **Sin esa guarda, un instrumento roto se lee exactamente igual que un contrato
  perfecto.** Es la misma exigencia que ya llevan la sonda de concurrencia y el recuento de
  tablas de esta página.

**Hasta que exista**, la columna Errores queda cubierta por revisión humana y por nada más. Lo
observó `implementation-frontend` leyendo el documento antes de construir contra él; el
mecanismo que lo cierra es de `qa`.

---

## Usuarios — `UsuarioService`

`App\Dominios\Usuarios\Servicios\UsuarioService`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `autenticar(string $email, string $password): Usuario` | correo y contraseña | el usuario autenticado | CREDENCIALES_INVALIDAS | implementado | RF-001 |
| `crear(DatosUsuario $datos): Usuario` | nombre, email, password, rol | el usuario creado | DOCUMENTO_DUPLICADO **(mal nombrado: es un correo repetido, ver la nota al pie)** | implementado | RF-002 |
| `actualizar(int $id, DatosUsuario $datos, ?Usuario $actor = null): Usuario` | campos a cambiar; `$actor` impide que un administrador se degrade o desactive a sí mismo | el usuario actualizado | RECURSO_NO_ENCONTRADO, DOCUMENTO_DUPLICADO, NO_AUTORIZADO | implementado | RF-002 |
| `listar(?string $buscar = null, int $pagina = 1): LengthAwarePaginator` | búsqueda y página | página de usuarios, sin el hash de contraseña | — | implementado | RF-002 |

> **`DOCUMENTO_DUPLICADO` acá está mal nombrado y se conserva a propósito.** Lo que se repite
> es el correo, no un documento. `docs/errores/manejo-errores.md` fija que un código de
> unicidad se nombra `ENTIDAD_CAMPO_DUPLICADO`; este es anterior a esa regla y renombrarlo es
> un cambio incompatible del contrato. Queda anotado acá para que nadie deduzca del código que
> el sistema valida documentos de usuario.

## Catálogo — `CategoriaService`, `ProductoService`

`App\Dominios\Catalogo\Servicios\`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `CategoriaService::crear(DatosDeCatalogo $datos): Categoria` | nombre, descripción | la categoría creada | CATEGORIA_NOMBRE_DUPLICADO | implementado | RF-003 |
| `CategoriaService::actualizar(int $id, DatosDeCatalogo $datos): Categoria` | campos a cambiar | la categoría actualizada | RECURSO_NO_ENCONTRADO, CATEGORIA_NOMBRE_DUPLICADO | implementado | RF-003 |
| `CategoriaService::listar(bool $incluirInactivas = false): Collection` | si incluye las inactivas | categorías | — | implementado | RF-003 |
| `CategoriaService::encontrar(int $id): Categoria` | identificador | la categoría | RECURSO_NO_ENCONTRADO | implementado | RF-003 |
| `ProductoService::crear(DatosDeCatalogo $datos): Producto` | código, nombre, categoría, unidad, precios, stock mínimo | el producto creado | PRODUCTO_CODIGO_DUPLICADO, PRODUCTO_PRECIO_MAYOR_INVALIDO, RECURSO_NO_ENCONTRADO | implementado | RF-004 |
| `ProductoService::actualizar(int $id, DatosDeCatalogo $datos, ?Usuario $actor = null): Producto` | campos a cambiar; el código no es modificable. `$actor` queda en la auditoría del cambio de precio | el producto actualizado | RECURSO_NO_ENCONTRADO, PRODUCTO_PRECIO_MAYOR_INVALIDO | implementado | RF-004 |
| `ProductoService::listar(?string $buscar = null, ?int $categoriaId = null, bool $soloActivos = true, int $pagina = 1): LengthAwarePaginator` | búsqueda, categoría, solo activos, página | página de productos | — | implementado | RF-004 |
| `ProductoService::encontrar(int $id): Producto` | identificador | el producto | RECURSO_NO_ENCONTRADO | implementado | RF-004 |

> **El catálogo no trae stock.** `listar()` y `encontrar()` devuelven el producto, no su
> existencia. El stock disponible lo sirve `ConsultaDeInventarioService::stock()`, que es quien
> conoce los lotes y sabe qué está vencido. Una pantalla que necesite ambas cosas invoca los dos
> servicios.

## Proveedores y clientes — `ProveedorService`, `ClienteService`

`App\Dominios\Proveedores\Servicios\`, `App\Dominios\Clientes\Servicios\`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `ProveedorService::crear(DatosDeEntrada $datos): Proveedor` | RUC, razón social, contacto | el proveedor creado | DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO | implementado | RF-005 |
| `ProveedorService::actualizar(int $id, DatosDeEntrada $datos): Proveedor` | campos a cambiar; el documento no es modificable | el proveedor actualizado | RECURSO_NO_ENCONTRADO | implementado | RF-005 |
| `ProveedorService::listar(?string $buscar = null, bool $soloActivos = true, int $pagina = 1): LengthAwarePaginator` | búsqueda, solo activos, página | página de proveedores | — | implementado | RF-005 |
| `ProveedorService::encontrar(int $id): Proveedor` | identificador | el proveedor | RECURSO_NO_ENCONTRADO | implementado | RF-005 |
| `ClienteService::crear(DatosDeEntrada $datos): Cliente` | tipo y número de documento, nombre, dirección, contacto | el cliente creado | DOCUMENTO_INVALIDO, DOCUMENTO_DUPLICADO | implementado | RF-010 |
| `ClienteService::actualizar(int $id, DatosDeEntrada $datos): Cliente` | campos a cambiar; el documento no es modificable | el cliente actualizado | RECURSO_NO_ENCONTRADO | implementado | RF-010 |
| `ClienteService::listar(?string $buscar = null, int $pagina = 1): LengthAwarePaginator` | búsqueda, página | página de clientes | — | implementado | RF-010 |
| `ClienteService::encontrar(int $id): Cliente` | identificador | el cliente | RECURSO_NO_ENCONTRADO | implementado | RF-010 |

> **La caja busca clientes con `listar($buscar)`, no con un método propio.** El contrato
> declaraba un `buscar(texto, limite)` que nunca existió; la búsqueda de RF-011 se resuelve con
> el mismo `listar`, acotado por el término. Vale lo mismo para productos.

## Inventario — `InventarioService`, `ConsultaDeInventarioService`

`App\Dominios\Inventario\Servicios\`

`InventarioService` es la **única puerta de escritura del stock**. Ninguna otra clase escribe
en lotes ni en el kardex; una prueba de arquitectura lo verifica (S-04-B/UT-02).
`ConsultaDeInventarioService` es la puerta de **lectura**, y es la que aplica la proyección por
rol.

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `InventarioService::ingresar(int $productoId, string $cantidad, string $costoUnitario, string $codigoLote, string $fechaVencimiento, string $origenTipo, int $origenId, int $usuarioId): Lote` | datos de una línea de compra | el lote creado o incrementado | LOTE_VENCIMIENTO_PASADO, PRODUCTO_INACTIVO, RECURSO_NO_ENCONTRADO | implementado | RF-006, RF-007 |
| `InventarioService::ajustar(int $loteId, string $cantidadNueva, string $motivo, ?string $observacion, int $usuarioId): Lote` | ajuste manual | el lote ajustado | AJUSTE_SIN_MOTIVO, AJUSTE_CANTIDAD_NEGATIVA, RECURSO_NO_ENCONTRADO | implementado | RF-009 |
| `InventarioService::descontarPorVencimiento(int $productoId, string $cantidad, string $origenTipo, int $origenId, int $usuarioId, ?string $hoy = null): array` | producto y cantidad a sacar | reparto: lista de porciones `(loteId, cantidad, costoUnitario)` en orden de vencimiento | STOCK_INSUFICIENTE, LOTE_VENCIDO, PRODUCTO_INACTIVO, RECURSO_NO_ENCONTRADO | implementado | RF-008, RF-011 |
| `InventarioService::stockDisponible(int $productoId, ?string $hoy = null): string` | producto | existencia no vencida, como decimal en texto | RECURSO_NO_ENCONTRADO | implementado | RF-008 |
| `InventarioService::lotesDe(int $productoId): Collection` | producto | sus lotes con existencia | RECURSO_NO_ENCONTRADO | implementado | RF-008 |
| `ConsultaDeInventarioService::stock(Usuario $actor, ?string $buscar = null, ?int $categoriaId = null, bool $soloConStock = false, int $pagina = 1): LengthAwarePaginator` | actor, búsqueda, categoría, solo con stock, página | productos con su stock y sus lotes por vencimiento; **sin costo si el actor es vendedor** | — | implementado | RF-008 |
| `ConsultaDeInventarioService::kardex(int $productoId, ?string $desde = null, ?string $hasta = null, int $pagina = 1): LengthAwarePaginator` | producto y rango | página de movimientos con origen y responsable | RECURSO_NO_ENCONTRADO | implementado | RF-008 |
| `lotesPorVencer(int $dias)` | días de anticipación | lotes por vencer y vencidos con existencia, ordenados por urgencia | — | pendiente S-07-B | RF-018 |
| `productosBajoMinimo()` | — | productos activos en o bajo su stock mínimo | — | pendiente S-07-B | RF-019 |

`descontarPorVencimiento` **no** cobra ni registra la venta: solo mueve stock. Debe invocarse
dentro de la transacción que abre `VentaService`.

> **Las cantidades y los costos viajan como `string`, no como `float`.** Es deliberado: son
> decimales exactos y un `float` los redondea. Quien los consuma no los convierte a número para
> operar — los compara y los suma con aritmética decimal.

## Compras — `CompraService`

`App\Dominios\Compras\Servicios\CompraService`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `registrar(DatosDeEntrada $datos, int $usuarioId): Compra` | proveedor, documento, fecha y líneas con lote y vencimiento | la compra con sus lotes generados | COMPRA_SIN_LINEAS, COMPRA_DOCUMENTO_DUPLICADO, LOTE_VENCIMIENTO_PASADO, PRODUCTO_INACTIVO | implementado | RF-006, RF-007 |
| `listar(?string $desde = null, ?string $hasta = null, ?int $proveedorId = null, int $pagina = 1): LengthAwarePaginator` | rango de fechas, proveedor, página | página de compras | — | implementado | RF-006 |
| `encontrar(int $id): Compra` | identificador | la compra con líneas y lotes | RECURSO_NO_ENCONTRADO | implementado | RF-006 |

## Ventas — `VentaService`

`App\Dominios\Ventas\Servicios\VentaService`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `registrarUnaSolaVez(string $claveDeOperacion, DatosDeEntrada $datos, Usuario $actor): Venta` | clave de idempotencia más lo de `registrar` | la venta; repetir la clave devuelve la misma, no crea otra | los de `registrar` | implementado | RF-011, RF-012, RF-013 |
| `registrar(DatosDeEntrada $datos, Usuario $actor): Venta` | cliente, tipo de comprobante, medio de pago, líneas con producto, cantidad y tipo de precio | la venta con su reparto por lote y su comprobante en estado `PENDIENTE` | VENTA_SIN_LINEAS, STOCK_INSUFICIENTE, LOTE_VENCIDO, FACTURA_REQUIERE_RUC, BOLETA_REQUIERE_DOCUMENTO, TIPO_PRECIO_INVALIDO, SERIE_NO_CONFIGURADA, PRODUCTO_INACTIVO | implementado | RF-011, RF-012, RF-013 |
| `listar(Usuario $actor, ?string $desde = null, ?string $hasta = null, ?string $estadoComprobante = null, int $pagina = 1): LengthAwarePaginator` | actor, rango, estado de comprobante, página | página de ventas; **acotada a las propias si el actor es vendedor** | — | implementado | RF-011, RF-020 |
| `encontrar(int $id, Usuario $actor): Venta` | identificador y actor | la venta con líneas, reparto por lote y estado del comprobante | RECURSO_NO_ENCONTRADO | implementado — **cambia a `array` al integrar `a805f4a`** | RF-011 |
| `reporteVentas(string $desde, string $hasta)` | rango | total, desglose por comprobante y medio de pago, detalle | — | pendiente S-07-B | RF-020 |
| `reporteUtilidad(string $desde, string $hasta, ?int $productoId)` | rango y producto opcional | ingreso, costo real por lote y utilidad, total y por producto | — | pendiente S-07-B | RF-021 |

`registrar` es el método más delicado del sistema. En una sola transacción: valida, descuenta
por FEFO, escribe kardex, reserva correlativo y crea el comprobante. Si algo falla, no queda
nada.

`encontrar(id, actor)` con una venta ajena para un vendedor produce `RECURSO_NO_ENCONTRADO`,
no `NO_AUTORIZADO`: no se revela que la venta existe.

> **El cambio de retorno de `encontrar` está aprobado y todavía no integrado.** `a805f4a`
> —aprobado por `qa` el 2026-08-20— lo pasa de `Venta` a `array<string, mixed>` para dejar el
> costo fuera de lo que ve el vendedor: el modelo entero lo llevaba en el reparto y en el lote.
> **Hasta que ese commit esté en `develop`, la firma vigente es la que dice la fila.** Esta
> línea se borra cuando se integre; citar la firma nueva antes de eso es exactamente el error
> que este proyecto ya cometió dos veces al despachar contra trabajo sin fusionar.

## Comprobantes — `SerieComprobanteService`, `ComprobanteService`, `ResumenDiarioService`

`App\Dominios\Comprobantes\Servicios\`

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `SerieComprobanteService::reservarCorrelativo(string $tipoComprobante): array` | tipo de comprobante | el siguiente correlativo, con la fila de la serie bloqueada | SERIE_NO_CONFIGURADA | implementado | RF-014 |
| `SerieComprobanteService::crear(string $tipo, string $serie)` | tipo y serie | la serie creada | SERIE_DUPLICADA | pendiente S-06-B | RF-014 |
| `SerieComprobanteService::listar()` | — | series con su correlativo actual | — | pendiente S-06-B | RF-014 |
| `ComprobanteService::listar(...)` | estado, rango, página | página de comprobantes, con pendientes y rechazados primero | — | pendiente S-06-B | RF-016 |
| `ComprobanteService::reenviar(int $id)` | identificador | el comprobante encolado de nuevo | COMPROBANTE_NO_REENVIABLE, RECURSO_NO_ENCONTRADO | pendiente S-06-B | RF-016 |
| `ResumenDiarioService::generar(string $fechaReferencia)` | fecha | el resumen creado y encolado | BOLETA_YA_RESUMIDA | pendiente S-06-B | RF-017 |
| `ResumenDiarioService::listar(?string $desde, ?string $hasta, int $pagina)` | rango | página de resúmenes con su estado | — | pendiente S-06-B | RF-017 |

`reservarCorrelativo` es de uso interno del backend: lo invoca `VentaService` dentro de su
transacción, y falla con una excepción si se lo llama fuera de una. **El frontend nunca lo
llama.**

> **Configurar series entró en S-06-B el 2026-08-20, y hasta ese día no estaba en ningún
> sprint.** RF-014 tiene dos mitades: asignar el correlativo —hecha en S-05-B— y que el
> administrador configure la serie, que no implementaba ningún RFC, no tenía pantalla en
> `docs/frontend/experiencia.md` y sí tenía dos filas en la matriz de permisos. No se notaba
> porque las pruebas insertan la serie directamente en la base; `qa` verificó después que
> `series_comprobante` es **la única tabla del sistema sin ningún servicio que la cree**, así
> que ahí la inserción a mano no era comodidad, era el único camino.
>
> Se habría notado en S-06-B, el primer sprint que emite de verdad: sin una serie configurada,
> `registrar` rechaza con `SERIE_NO_CONFIGURADA` antes de llegar a SUNAT. Entró en ese mismo
> sprint como unidad previa, con la pantalla en S-05-F, y `SERIE_DUPLICADA` se agregó a la
> taxonomía de errores al mismo tiempo.

## Emisión electrónica — interfaz `EmisorElectronico`

Frontera con SUNAT. Ningún dominio conoce Greenter; solo esta interfaz.

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `enviarComprobante(comprobante)` | el comprobante a emitir | resultado: aceptado con su CDR, o rechazado con código y mensaje de SUNAT | SUNAT_NO_DISPONIBLE, SUNAT_RECHAZO, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO | pendiente S-06-B | RF-015 |
| `enviarResumen(resumen)` | el resumen de boletas | el identificador de consulta que devuelve SUNAT | SUNAT_NO_DISPONIBLE, CERTIFICADO_NO_DISPONIBLE, CERTIFICADO_VENCIDO | pendiente S-06-B | RF-017 |
| `consultarResumen(ticket)` | identificador de consulta | el resultado del resumen | SUNAT_NO_DISPONIBLE | pendiente S-06-B | RF-017 |
| `diasParaVencimientoCertificado()` | — | días restantes del certificado | CERTIFICADO_NO_DISPONIBLE | pendiente S-06-B | RNF-005 |

**Solo los trabajos en segundo plano invocan esta interfaz.** Ni la venta ni ningún componente
de la interfaz la llaman directamente: eso rompería la garantía de que la caja no depende de
SUNAT (RNF-002).

## Auditoría — `AuditoriaService`

`App\Compartido\Auditoria\AuditoriaService` — **no vive bajo `app/Dominios/`**: lo usan varios
dominios y no es de ninguno.

| Método | Entrada | Devuelve | Errores | Estado | Deriva de |
|---|---|---|---|---|---|
| `registrar(...)` | entidad, identificador, acción, valores anteriores y nuevos, usuario, origen | nada | — | implementado | RNF-004 |

Los valores se arman con **lista explícita de campos auditables por entidad**, nunca
serializando el objeto completo, para que un campo sensible nuevo no quede expuesto por
omisión.

Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
Sincronización con el código aprobada por (Arquitectura): sesión de Arquitectura del 2026-08-20 — fecha: 2026-08-20
