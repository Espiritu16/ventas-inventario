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

## Regla transversal — un componente que escribe comprueba el permiso él mismo

**Aplica a toda pantalla, presente y futura. No es específica de usuarios.**

Proteger la ruta de una pantalla **no protege sus componentes**. Los métodos públicos
de un componente Livewire no viajan por la ruta que sirve la pantalla: viajan por
`POST /_livewire/update`, que esta misma matriz declara como "exige sesión activa" y
que **no distingue rol**. Un usuario con sesión válida pero sin permiso puede invocar
esos métodos sin pasar nunca por la pantalla.

Y el servicio de dominio tampoco lo cubre, ni debe: no sabe quién lo llama, y hacerlo
consciente del rol lo convertiría en otra cosa.

Por lo tanto: **todo componente comprueba el permiso donde sirve o escribe los datos,
derivándolo de esta matriz** — no de una lista propia, por la misma razón por la que el
menú lo deriva de acá: una sola fuente.

**"Donde sirve los datos" no es el montaje.** `mount()` corre una sola vez; en cada
interacción posterior el componente se **hidrata desde el snapshot** que el navegador
tiene guardado, y `render()` vuelve a consultar sin pasar por el montaje. Un permiso
comprobado solo al montar protege la primera carga y nada más: si el rol de la persona
cambia mientras la pantalla está abierta, sigue viendo datos que ya no le corresponden
hasta que recargue.

Y esto **no lo cubre el middleware**: el endpoint de actualización de Livewire exige
sesión activa pero no comprueba rol, precisamente porque es el mismo endpoint para todos
los componentes.

Demostrado por HTTP real en la validación de S-01-F: a un administrador se le cambió el
rol a vendedor desde la propia pantalla de usuarios, y con su snapshot y su sesión el
listado **siguió respondiendo con los nombres y correos de todos**. La escritura sí
estaba protegida —un vendedor no podía darse de alta como administrador— y la lectura
no.

Una distinción que costó separarlas y conviene recordar: un usuario **desactivado** sí
lo frena el middleware, que comprueba `activo`. Un usuario **degradado de rol** no lo
frena nadie. Parecían el mismo agujero y son dos, y solo uno existe.

**La forma del error, que es lo que conviene reconocer y no la lista de casos:**
confundir el lugar donde algo se decide **una vez** con el lugar donde se usa **cada
vez**. El menú se arma una vez y la ruta se pide cada vez. La ruta se autoriza al
navegar y el componente se invoca cada vez. El montaje corre una vez y el render corre
cada vez.

Quien lea "comprobá donde se sirven los datos" va a pensar que es obvio. No lo es: las
tres veces que se equivocó, a quien lo implementaba le pareció que ya estaba cubierto, y
las tres veces **había una prueba en verde respaldándolo**. La formulación es de
`implementation-frontend`, que cometió los tres en orden y los reconoció como uno solo.

Los tres pisos, que conviene ver juntos porque cada uno parecía suficiente hasta que
apareció el siguiente:

1. Ocultar un ítem del menú no es control de acceso. Protege la vista, no la ruta.
2. Proteger la ruta no es proteger el componente. Protege la navegación, no la
   invocación.
3. El servicio no protege nada de esto. Ejecuta la operación; no sabe quién pidió.

Detectado por `implementation-frontend` al cerrar S-01-F, a partir de esta misma nota:
comprobó su propio componente y encontró que un vendedor podía darse de alta como
administrador sin pasar por la pantalla. Corregido con pruebas de regresión y
verificado por mutación.

**Alcance de lo demostrado, para no exagerarlo:** la sonda usó `Livewire::test`, que no
pasa por el middleware HTTP. Eso demuestra que faltaba la guarda del componente, no que
la escalada fuera explotable de punta a punta —Livewire firma los snapshots y reaplica
el middleware de la página donde se montó—. Se corrige igual porque hacer depender el
control de acceso del comportamiento interno de un paquete de terceros es exactamente
lo que esta matriz existe para evitar.

**Pendiente estructural, asignado a S-02-F:** hoy la regla depende de que quien escriba
cada componente se acuerde. Eso no escala — S-02-F, S-03-F y S-04-F traen muchas
pantallas, y la de caja mueve stock y correlativos. La comprobación debe pasar a
aplicarse **por mecanismo**, no por disciplina: que un componente que escribe sin
declarar su permiso falle, en lugar de quedar abierto. La forma concreta la proponen
`implementation-backend` y `implementation-frontend` juntos, y la aprueba Arquitectura.

## Rutas que registra Livewire

Livewire registra rutas propias al instalarse. Bajo deny-by-default se rechazarían
todas, y con ellas dejaría de funcionar cualquier componente: el endpoint de
actualización es por donde viajan **todas** las interacciones. El síntoma aparecería
en el código de quien construye la pantalla, no acá, así que se declara antes de que
eso ocurra.

**El prefijo NO se declara como cadena literal, y tampoco se fija a un valor
estático.** Livewire lo deriva de `APP_KEY`: `substr(hash('sha256', app.key .
'livewire-endpoint'), 0, 8)`. Eso significa que es **distinto en cada instalación** —
la máquina de quien implementa, la de quien valida, CI y el servidor tienen prefijos
distintos. Una declaración con la cadena literal sería correcta solo donde se generó
y dejaría de aplicar en todas las demás, en silencio.

Fijarlo a un valor estático tampoco corresponde: el paquete lo deriva a propósito
para que un escáner genérico no pueda apuntarle a una ruta conocida. Es oscuridad,
no control de acceso, pero apagarla de rebote para resolver un problema de
sincronización de documentos sería tomar una decisión de seguridad por el motivo
equivocado.

**Las rutas se declaran relativas al prefijo, y el prefijo se resuelve en cada
petición desde la misma fuente que registra las rutas reales** (`EndpointResolver`
del paquete). Así no hay dos valores que mantener iguales: hay uno solo, leído de
donde nace. Una declaración que se deriva de la misma fuente que la ruta no puede
desalinearse de ella.

| Ruta (relativa al prefijo) | Tratamiento | Por qué |
|---|---|---|
| `GET` de assets estáticos bajo el prefijo — `*.js`, `*.css`, `*.map` | Pública | Assets estáticos. No tocan datos ni estado. Mismo criterio que `GET /up`. **Se declara por patrón, no uno por uno**: en Livewire 4.4.1 el paquete registra `livewire.js` y dos mapas de código, pero ese conjunto es un detalle interno del paquete y puede cambiar entre versiones. Una lista enumerada tendría que reeditarse en cada actualización, y el modo de fallo de olvidarlo es que un asset deje de cargar sin que nada lo anuncie |
| `POST /update` | **Exige sesión**, salvo para los componentes de la lista de abajo | Ver el razonamiento |
| `POST /upload-file` | **No autorizada** — se rechaza | Ningún RF del horizonte pide subir archivos. Una superficie que nadie usa no se deja abierta |
| `GET /preview-file/{f}` | **No autorizada** — se rechaza | Ídem |
| `GET/PUT /storage/{path}` (ruta absoluta, del framework) | **No autorizada** — se rechaza | Ruta del driver de disco local. Ningún RF la necesita hoy |

### Por qué `POST /_livewire/update` no es infraestructura

Es el canal por el que se invoca cualquier método público de cualquier componente
montado. Declararlo público a secas no sería una fila más en la tabla: movería la
garantía de control de acceso desde este documento hacia el comportamiento interno
de un paquete de terceros. Livewire efectivamente reaplica el middleware de la
petición original, pero entonces la protección dejaría de ser nuestra y pasaría a
depender de que ese comportamiento no cambie en una versión futura.

Por eso el control se ejerce en nuestra capa: el endpoint exige sesión activa, y solo
los componentes declarados abajo pueden invocarse sin ella. Esto no reemplaza lo que
Livewire hace por su cuenta; se suma.

### Componentes accesibles sin sesión — lista cerrada

| Componente | Por qué | Deriva de |
|---|---|---|
| `App\Dominios\Usuarios\Livewire\InicioDeSesion` | Es el único que, por definición, se usa antes de tener sesión | RF-001 |

Agregar un componente a esta lista es una decisión de Arquitectura, nunca del sprint
que lo necesita. Un componente que no esté acá y se invoque sin sesión se rechaza.

**El nombre de la clase queda fijado acá, por Arquitectura, antes de que exista.** La
lista se materializa en `app/Compartido/Autorizacion/MatrizDePermisos.php`, que es
ruta de `implementation-backend`; `implementation-frontend` crea el componente pero
no puede declararlo. Sin esta decisión anticipada, S-01-F quedaría bloqueado su
primer día por el mismo desajuste de rutas que ya obligó a asignar
`routes/backend.php`. S-01-B deja la entrada declarada por adelantado y S-01-F debe
crear el componente **con ese nombre exacto**: si necesita otro, escala a
Arquitectura, no lo renombra.

Decidido por Arquitectura el 2026-08-19, a partir del hallazgo que reportó
`implementation-backend` al instalar Livewire en UT-06 de S-01-B.

## Matriz de permisos

**Cómo leer las rutas de esta tabla.** Este proyecto no expone una API HTTP entre
backend y frontend: los componentes Livewire invocan los servicios de dominio en el
mismo proceso (ADR-0005, `docs/frontend/integracion.md`, `docs/contratos/servicios-de-dominio.md`).
Por lo tanto, salvo las de infraestructura declaradas arriba y `POST /login` /
`POST /logout` —que son operaciones HTTP genuinas de sesión—, **cada ruta de esta
tabla es la pantalla que expone esa operación**, no un endpoint que devuelva datos a
un cliente. La autorización se aplica igual sobre ella, desde el servidor y con
deny-by-default: ocultar un ítem del menú no es control de acceso.

**Las filas cuyo verbo no es `GET` describen operaciones, no rutas HTTP.** Crear y
actualizar ocurren dentro de la pantalla, invocando el servicio de dominio en el mismo
proceso; no existe una ruta `POST /usuarios` ni `PATCH /usuarios/{id}` que atender. La
autorización se aplica igual —el componente comprueba el permiso antes de invocar el
servicio, y el servicio no confía en el componente— pero **no esperes encontrar esas
rutas en el enrutador**. Se conservan en la tabla porque expresan quién puede hacer
qué, que es lo que esta matriz declara; lo que cambió es dónde se ejecuta, no quién
está autorizado. Precisado por Arquitectura el 2026-08-19 a partir de la observación de
`implementation-frontend` al cerrar S-01-F: las filas estaban declaradas y las rutas no
existían, y sin esta nota alguien las buscaría.

`GET /login` es la única pantalla accesible sin sesión. Sin esa fila, deny-by-default
produce un catch-22 —hace falta sesión para ver la pantalla donde se obtiene la
sesión—, que es como se detectó: `implementation-frontend` lo reportó al implementar
S-01-F, y la propia tabla de infraestructura ya exigía esa página al declarar que
`GET /` redirige ahí. Fila agregada por Arquitectura el 2026-08-19.

| Actor | Rol técnico | Recurso/Operación | Acción | Condición/alcance | Permitido | Deriva de |
|---|---|---|---|---|---|---|
| Anónimo | (ninguno) | GET /login | ver la pantalla de acceso | — | Sí | RF-001 |
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
