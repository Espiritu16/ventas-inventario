# ADR-0006 — La interfaz no consume una API HTTP interna

- Estado: aprobado
- Fecha: 2026-08-19
- Decide: Arquitectura
- Deriva de: ADR-0005 (un solo repositorio fullstack)

## Contexto

ADR-0005 estableció un repositorio único con Livewire. `docs/frontend/integracion.md` y
`docs/contratos/servicios-de-dominio.md` dicen, cada uno por su lado, que no hay cliente
HTTP entre la interfaz y el servidor: los componentes invocan los servicios de dominio en
el mismo proceso, y el contrato entre los dos frentes son las **firmas de esos métodos**,
no rutas.

Pero los contratos por dominio se redactaron con forma de API REST —`GET /productos`,
`POST /clientes`, `PATCH /proveedores/{id}`— y los sprints de backend los implementaron
así, correctamente, porque eso decía el documento aprobado.

El resultado aparece cuando llega el sprint de frontend que construye esa pantalla: los
dos frentes reclaman la misma URI, uno para una vista y otro para JSON. **Git no detecta
conflicto** —los archivos de rutas son distintos— y el que se carga primero tapa al otro,
rompiendo pruebas que hoy pasan.

Ocurrió con `/usuarios` en S-01-F y se resolvió enmendando ese contrato. Volvió a
aparecer al preparar S-02-F, esta vez **cuatro veces de una**: categorías, productos,
proveedores y clientes, con trece endpoints registrados. Y volvería a aparecer en
S-03-F, S-04-F, S-05-F y S-06-F para compras, inventario, ventas y comprobantes.

Corregirlo contrato por contrato, cuando el choque aparece, garantiza que vuelva a
aparecer.

## Decisión

**Todo recurso de dominio se sirve como pantalla, no como endpoint HTTP.** La operación
la ejecuta el servicio de dominio invocado en el mismo proceso por el componente.

Las únicas rutas HTTP que el sistema expone son:

| Ruta | Por qué es HTTP genuino |
|---|---|
| `POST /login`, `POST /logout` | Transiciones de sesión que el navegador ejecuta como envío de formulario. No son lecturas de datos |
| `GET /`, `GET /up` | Infraestructura. Declaradas en `docs/requisitos/actores-permisos.md` |
| El endpoint de Livewire y sus assets | Los registra el paquete. Declarados y acotados en la misma matriz |

**Cualquier endpoint HTTP nuevo que no esté en esa tabla necesita un consumidor real y
declarado.** No basta con que un contrato lo describa: hay que poder nombrar quién lo
llama. Un endpoint sin consumidor es superficie que nadie usa, y este proyecto ya decidió
—con las rutas de subida de archivos de Livewire— que una superficie que nadie usa no se
deja abierta.

**Los contratos por dominio describen pantallas.** Su `Request`/`Response` describe lo que
la pantalla acepta y muestra.

### Pantalla u operación: lo decide el destino, no el verbo

La regla **no** es "`GET` es pantalla y el resto operación". Es:

- **Pantalla**: un destino al que la persona navega. Cuáles existen lo declara
  `docs/frontend/experiencia.md`, que aprueba el usuario. Esa es la autoridad.
- **Operación**: algo que ocurre **dentro** de una pantalla, sin cambiar de destino.
  Puede ser `GET` — leer el detalle de un producto para abrir su formulario de edición es
  un `GET` y no es una pantalla, porque `experiencia.md` dice que el catálogo se gestiona
  "en la misma pantalla, sin cambiar de ruta".

Una fila de la matriz cuyo recurso no corresponde a ninguna pantalla de `experiencia.md`
describe una operación. Si alguien cree que necesita una pantalla nueva, eso se agrega a
`experiencia.md` y lo aprueba el usuario, no se deduce de que la fila diga `GET`.

### Al cambiar el significado de los recursos hay que reauditar la matriz

Este ADR **cambió el sentido de toda la columna de recursos** de la matriz de permisos.
Las filas se escribieron cuando cada recurso era un endpoint JSON, y sus condiciones de
alcance —"sin columna de costo", "sin margen"— describían **proyecciones de campos en una
respuesta**, no acceso a una pantalla completa.

Con el cambio, tres filas pasaron a significar algo que nadie decidió: el vendedor tenía
`Sí` sobre `GET /categorias`, `GET /productos` y `GET /productos/{id}`, escrito cuando eso
era el endpoint que la caja consultaba. Al volverse esos recursos **la pantalla de gestión
de catálogo**, esas filas le daban acceso a una pantalla que `experiencia.md` declara solo
para administrador — con las columnas de costo ocultas, pero con los botones de alta y
edición a la vista y el rechazo recién al intentar escribir.

Corregido el 2026-08-19: las tres pasan a `No`. **El acceso del vendedor no se pierde**:
lo que necesita es buscar productos en la caja, que es otra pantalla (`/ventas/nueva`, sí
suya) e invoca el mismo servicio bajo el permiso de esa pantalla.

Es el patrón de siempre un piso más arriba: **algo que era correcto sigue escrito igual y
ya no significa lo mismo, y nada falla al leerlo**. Lo detectó `implementation-frontend`
al pasar el ADR por sus cuatro pantallas antes de implementar.

## Consecuencias

- Se retiran los trece endpoints JSON de catálogo, proveedores y clientes registrados en
  `routes/backend.php`, junto con sus controladores y las pruebas que los ejercen por
  HTTP. **Los servicios de dominio no se tocan**: siguen siendo el contrato entre frentes
  y quien ejecuta la operación.
- No hace falta enmendar los contratos uno por uno cuando llegue cada sprint de frontend.
  Este ADR los gobierna a todos; cada contrato lo referencia.
- Los sprints de backend que vengan —compras, inventario, ventas, comprobantes— **no
  registran rutas HTTP para sus recursos**. Si alguno cree necesitarlo, escala antes de
  implementar.
- El trabajo ya hecho no fue un error: S-01-B y S-02-B implementaron lo que sus contratos
  decían, y QA los aprobó contra el criterio vigente. Se retira por una decisión
  posterior, no por un defecto.

## Alternativas descartadas

- **Servir las pantallas en otras URIs y conservar los endpoints.** Deja la URL natural
  ocupada por algo que nadie consume, y obliga a inventar direcciones para lo que la
  persona sí usa.
- **Seguir enmendando contrato por contrato.** Es lo que se venía haciendo. Cuesta una
  interrupción por dominio y no cierra la clase: quedan cuatro dominios por delante.

## Cómo se detectó

`implementation-frontend` lo encontró al leer el RFC de S-02-F **antes de implementar**,
sin worktree montado, y no lo tocó porque no era su ruta. Lo mismo había hecho con
`/usuarios` en S-01-F. La segunda vez planteó además la pregunta de fondo —si convenía
una decisión transversal en vez de repetir la corrección— que es la que produjo este ADR.
