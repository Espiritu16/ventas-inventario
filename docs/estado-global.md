---
project: ventas-inventario
source_status: CANONICA
baseline: documentación inicial aprobada 2026-08-19
active_phase: ola-4
active_status: EN_PROGRESO
last_completed_phase: ola-2 (S-01-B, S-DO-01)
bootstrap_status: EN_PROGRESO
planning_horizon_status: COMPLETA
current_rfc_batch: []
planning_scope: [RF-001, RF-002, RF-003, RF-004, RF-005, RF-006, RF-007, RF-008, RF-009, RF-010, RF-011, RF-012, RF-013, RF-014, RF-015, RF-016, RF-017, RF-018, RF-019, RF-020, RF-021, RNF-001, RNF-002, RNF-003, RNF-004, RNF-005, RNF-006, RNF-007, RNF-008, RNF-010, RNF-011, RNF-012, RNF-013, RNF-014]
updated_at: 2026-08-19
repositories:
  - name: ventas-inventario
    path: ventas-inventario
    branch: develop
    integration_branch: develop
    protected_branch: main
sprints:
  - id: S-00
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-00
    base_sha: 99cd0618ec05f8386202813a2efa232724ec0bd8
    final_sha: ae2b0f73804c8b383dd970d91c1be379e305bc94
    merge_sha: 4e6af0e
    qa: APROBADO sobre f65efca con gobernanza c5c5389
    depends_on: []
    parallelizable_with: []
  - id: S-01-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-01-B
    base_sha: b99b936
    final_sha: 4156f116103cdf843bfeef84ab72798ca012760b
    merge_sha: db8ec3e
    qa: APROBADO sobre 31b84fa con gobernanza 20ef754
    depends_on: [S-00]
    parallelizable_with: [S-DO-01]
  - id: S-02-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-02-B
    base_sha: b1c7b13
    final_sha: 9c5c605105eade23f5b4e5ff250fa740f7efebc1
    merge_sha: 64d21e4
    qa: APROBADO sobre 67bd7d5 con gobernanza 92c1d1c
    depends_on: [S-01-B]
    parallelizable_with: [S-03-B, S-01-F]
  - id: S-03-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-03-B
    base_sha: 9304925
    final_sha: 6e8f710393489ee38b51b043fd83538ad7cce4e9
    merge_sha: 5261b44
    qa: APROBADO sobre 44ba6e3 con gobernanza caeddd2
    nota_de_ejecucion: se ejecutó en secuencia tras S-02-B pese a ser paralelizable — comparten database/migrations/ y config/
    depends_on: [S-01-B]
    parallelizable_with: [S-02-B, S-01-F]
  - id: S-04-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-04-B
    base_sha: e87aded
    final_sha: 4ddac6f1ee83f7782354a2953a0e4f47cb37b48c
    merge_sha: a2d2f48
    qa: APROBADO sobre 5aa956f con gobernanza 492b784
    depends_on: [S-02-B, S-03-B]
    parallelizable_with: [S-02-F]
  - id: S-05-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-05-B
    base_sha: a2d2f48
    final_sha: d51de537a8a6a79883f43129a9409255e9b97016
    qa: APROBADO sobre d51de53 con gobernanza 1650dfd
    depends_on: [S-04-B]
    parallelizable_with: [S-03-F]
  - id: S-06-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B]
    parallelizable_with: [S-07-B, S-08-B, S-04-F]
  - id: S-07-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B]
    parallelizable_with: [S-06-B, S-08-B, S-04-F]
  - id: S-08-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-B]
    parallelizable_with: [S-06-B, S-07-B]
  - id: S-01-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-01-F
    base_sha: b1c7b13
    final_sha: 2b26c193654d19d97a753b46afccb0c2a7a48294
    merge_sha: c6f7add
    qa: APROBADO sobre 0450853 con gobernanza 9304925
    depends_on: [S-01-B]
    parallelizable_with: [S-02-B, S-03-B]
  - id: S-02-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-02-F
    base_sha: e87aded
    final_sha: 0f436a4f2a3b6b045fff5c12a8070e9ad946cfa4
    merge_sha: 476d2c0
    qa: APROBADO sobre bd2f393 (final_sha 0f436a4) con gobernanza b1ca4ad
    depends_on: [S-02-B, S-03-B, S-01-F]
    parallelizable_with: [S-04-B]
  - id: S-03-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-B, S-01-F]
    parallelizable_with: [S-05-B]
  - id: S-04-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-05-B, S-01-F]
    parallelizable_with: [S-06-B, S-07-B]
  - id: S-05-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-06-B, S-01-F]
    parallelizable_with: [S-06-F]
  - id: S-06-F
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-07-B, S-01-F]
    parallelizable_with: [S-05-F]
  - id: S-09-B
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-04-F]
    parallelizable_with: [S-05-F, S-06-F]
  - id: S-DO-01
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: COMPLETADO
    branch: sprint/S-DO-01
    base_sha: b99b936
    final_sha: 3fc99a7bf8e1615b66a54d0f54b5bacc9749106e
    merge_sha: 24320bd
    qa: APROBADO sobre 605c240 con gobernanza c917aec
    depends_on: [S-00]
    parallelizable_with: [S-01-B]
  - id: S-QA-01
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-09-B, S-05-F, S-06-F]
    parallelizable_with: []
  - id: S-DO-02
    repository: ventas-inventario
    planning_status: LISTO
    execution_status: PLANIFICADO
    depends_on: [S-QA-01, S-DO-01]
    parallelizable_with: []
---

# Segunda promoción del día — 2026-08-20

`origin/main` recibe cinco ramas más sobre `develop@bb43001`, las cinco aprobadas por `qa`:
pruebas del menú derivadas de la matriz, extracción del manejo de rechazos a
`app/Compartido/Interfaz/`, guardián por vocabulario, mensajes del validador en español con
mapa de campos, y la comprobación de que ese mensaje llega a la pantalla.

Suite completa sobre la fusión: **529 / 1080 aserciones**, quince migraciones desde base
limpia, pint verde.

## El riesgo que tenía esta fusión, y cómo se descartó

`refactor/errores-de-pantalla` y `test/mensajes-en-pantalla` **se desarrollaron en paralelo
desde bases distintas y ninguna contenía a la otra**. La primera reorganizó cómo los cinco
componentes guardan el error; la segunda afirma sobre esas mismas propiedades.

Arquitectura leyó el trait y la prueba y concluyó que eran compatibles. `qa` no aceptó la
lectura: **mutó el trait para que `errorDeCampo` se llenara con un texto fijo**, y la prueba
de pantalla falla. Eso descarta el escenario que la lectura no podía descartar — que la prueba
estuviera leyendo una propiedad llenada por otra vía y quedándose verde por casualidad.

Confirmó además que ningún componente asigna `errorDeCampo` con contenido: los cinco solo la
ponen a `null`, y la única vía que la llena es el trait.

## La regla que cierra el día

Salió de un push forzado que **devolvió un mensaje de permiso denegado y sin embargo se había
ejecutado**. Quedarse con esa respuesta habría producido el reporte contrario al hecho, y
habría llevado a forzar de nuevo un push ya hecho. Lo resolvió `git ls-remote`, que no pasa
por ninguna referencia local.

Es la del SHA un nivel más arriba, y `qa` lo formuló mejor de lo que apareció:

> **La respuesta de quien ejecuta no es evidencia de lo ejecutado; la evidencia es el estado
> consultado aparte.**

Y su observación de por qué es más difícil de sospechar: **uno acepta el resultado de lo que
acaba de hacer con mucha menos resistencia que un dato de terceros.** El nombre de la rama es
un dato ajeno del que se desconfía; la respuesta del comando propio se siente como haber
mirado.

La familia entera, ordenada de menos a más sutil: el nombre de la rama miente porque sobrevive
a un rebase; la referencia `origin/...` local miente porque es una foto que solo se actualiza
con un fetch exitoso; y la respuesta de la herramienta miente porque describe el intento, no
el efecto.

## Una línea suelta, sin urgencia

Los cinco componentes usan `limpiarMensajes()` del trait, **pero dentro de su propio
`limpiarFormulario()` repiten las dos asignaciones a mano** en vez de llamarlo. Hoy es
idéntico funcionalmente. Es el modo de fallo clásico de una extracción incompleta: **si mañana
el trait suma una tercera propiedad de mensaje, `limpiarMensajes()` la limpiará y esos cinco
`limpiarFormulario()` no.** Lo observó `qa`. Para cuando alguien vuelva a tocar ese archivo.

---

# Promoción a `main` — 2026-08-20

`origin/main` en `ad42599`. Contiene `develop@8ae24d1` entero. Segunda promoción; la
anterior dejó `main` en `2e735c7` con las olas 1–3.

| Sprint | Qué entra | Validación |
|---|---|---|
| S-02-B | catálogo: categorías y productos | APROBADO |
| S-03-B | lotes, kardex y puerta única de escritura del stock | APROBADO |
| S-04-B | compras, consulta de inventario y kardex | APROBADO |
| S-05-B | venta con descuento FEFO, idempotencia y comprobante | APROBADO |
| S-01-F | acceso, menú y pantalla de usuarios | APROBADO |
| S-02-F | catálogo, productos, proveedores y clientes | APROBADO |

**Lo que hace válida esta promoción no es que las seis ramas estuvieran aprobadas.** Cada
una se validó por separado y las seis estaban verdes, pero `develop@8ae24d1` es un séptimo
artefacto que nadie había corrido. `qa` corrió la suite completa sobre un checkout limpio de
ese SHA antes de promover: Feature 493/976, Unit 12/17, quince migraciones desde base limpia,
pint y build en verde.

El riesgo concreto era real y estaba acotado: S-02-F escribió sus pruebas cuando un código de
producto mal formado devolvía `PRODUCTO_CODIGO_DUPLICADO`, y el fix de unicidad lo cambió. Se
dedujo leyendo que no las tocaba —las pruebas de pantalla afirman el campo, no el código— y
`qa` lo comprobó **ejecutando la pantalla real**: el campo señalado es `codigo` en los cuatro
casos y el duplicado legítimo conserva su mensaje de dominio. **Una suite verde no distingue
"las pruebas no dependen de eso" de "las pruebas no ejercitan ese camino"**; solo mirar la
interacción lo distingue.

## Dos líneas sueltas que quedaron de la validación, ninguna bloqueante

- **La rendija de `fecha_vencimiento`.** La lista de claves exactas de la proyección de la
  venta cierra la forma; lo que detiene un costo escondido *dentro* de un campo permitido son
  las aserciones de **valor**. `codigo_lote` y `cantidad` tienen su valor fijado;
  `fecha_vencimiento` no, y por ahí pasa un costo redondeado concatenado. Es artificial y
  nadie lo haría, pero la lección es transferible y quedó como regla: **las listas de claves
  cierran la forma, las aserciones de valor cierran el contenido, y hacen falta las dos.**
  Cierre de una línea, asignado a `implementation-backend`.
- **`UnaSolaTraduccionDeReglasTest` resiste el renombrado y no el cambio de forma.** Detecta
  una copia con `match` y otro nombre de método; **no** detecta una escrita con `if/elseif`. Y
  ese es el caso más probable de los dos: **quien copia se lleva el `match`, quien se hace el
  suyo escribe lo que le sale.** Un guardián que solo atrapa al que copia no protege del que
  reinventa. Propuesta de `qa`, adoptada: buscar la **conjunción de vocabularios** —códigos
  genéricos junto a nombres de reglas de validación— en vez de la sintaxis.

## Un hallazgo abierto: el usuario lee mensajes en inglés

`config/app.php` tiene `'locale' => env('APP_LOCALE', 'en')`, **no existe directorio `lang/`**,
y `docs/frontend/experiencia.md` declara que el texto mostrado es *"el mensaje en español de la
taxonomía"*. Las tres cosas verificadas. En la pantalla real de productos, un código con
espacio muestra `"The codigo field format is invalid."`

Es preexistente y **no lo introduce ningún sprint de hoy**. Pero el fix de unicidad amplió su
alcance en la dirección buena: ese caso antes respondía "Ya existe un producto con ese código"
—en español y **mentiroso**—, y ahora responde un mensaje veraz en inglés. Cambió una mentira
en castellano por una verdad en otro idioma, y al hacerlo **destapó una capa que el defecto
anterior tapaba**. Por eso no se rechazó: revertir reintroduce códigos que mienten, que es peor.

`qa` acotó la superficie ejecutando: el mensaje sale **en español donde hay mensaje de dominio
y en inglés donde cae al validador genérico**. No hay que traducir el framework entero; basta
con que los genéricos tengan traducción o con que cada servicio dé el suyo.

**RESUELTO el 2026-08-20.** El usuario decidió **traducir el validador con mapa de campos**.
Va como corrección en rama de fix y no como sprint nuevo: es una divergencia contra un
documento aprobado, no alcance nuevo — el mismo tratamiento que los dos defectos que destapó
el fix de unicidad. Despachado a `implementation-backend`; el detalle y sus dos condiciones
quedan en `docs/errores/manejo-errores.md`.

Lo que la decisión incorpora y no estaba a la vista al escalarla: **traducir no alcanza solo**,
porque los mensajes por defecto nombran el campo por su identificador técnico y la taxonomía
prohíbe la jerga técnica. El mapa que hace falta es la **novena instancia** del patrón de dos
fuentes, y se cierra con la misma prueba de consistencia que la matriz de permisos.

---

# Verificar un hecho vecino no es verificar la pregunta

**Registrado el 2026-08-20, después de que la misma forma apareciera cuatro veces en una
tarde, en los tres roles y en Arquitectura.** No es falta de rigor: las cuatro veces se
verificó algo *de verdad*, y las cuatro veces lo verificado no era lo que se estaba afirmando.

| Quién | Qué comprobó | Qué afirmó | Por qué no se seguía |
|---|---|---|---|
| `implementation-backend` | un `grep` de seis líneas después de cada llamada al validador | que `$codigosPorCampo` no tenía consumidores | tenía dos, y los dos caían fuera de la ventana |
| Arquitectura | que el enum no menciona nombres de regla | que **eso** era lo que impedía el falso positivo | lo impedía el prefijo `CodigoDeError::`; nunca se leyó qué contaba el detector |
| `qa` | que los `case` viven fuera de los métodos | que eso distinguía una declaración de una traducción | el enum ya usa `self::CAMPO_*` **dentro** de un método, y no es una traducción |
| `implementation-backend` | nada — venía razonado | que cada mitad de la conjunción del guardián sostenía un caso propio | quitar el mínimo de dos códigos **no hacía fallar ninguna prueba** |

**Lo que las une: se razonó sobre lo que el mecanismo *debería* mirar en vez de leer lo que
mira.** Y en los cuatro casos la comprobación hecha era cierta, lo que las vuelve difíciles de
detectar: no hay un dato falso del que tirar, hay un dato verdadero contestando otra pregunta.

Las tres reglas que salen, y las tres se pagaron el mismo día:

1. **Antes de afirmar por qué algo funciona, leé el mecanismo.** No alcanza con comprobar una
   propiedad del artefacto sobre el que el mecanismo opera. Fue lo que resolvió los cuatro
   casos, y siempre del mismo modo: alguien fue a leer el código en vez de deducirlo.
2. **La ventana de la búsqueda es parte de la búsqueda.** Un resultado vacío solo dice que no
   había nada *dentro de la ventana*. Vale para un `grep` acotado, para un `lsof` sin
   privilegios, y para un patrón mal escapado — que le pasó a Arquitectura media hora después
   de señalárselo a otro.
3. **Una afirmación sobre cobertura que se escribe en el código se mide, no se argumenta.** La
   cuarta fila iba a quedar escrita como comentario en el propio detector, razonada y sonando
   bien. Al medirla resultó que una de las dos condiciones no la sostenía **nada**, y era
   exactamente el tipo de condición que alguien borra en seis meses porque parece de más y no
   ve que rompa nada. Ahora cada mitad tiene una prueba que se cae si desaparece.

**La defensa contra un resultado vacío no es recordar que puede ser falso: es no aceptarlo sin
una segunda vía que lo confirme.** Es la afinación de `qa` y es lo que vuelve accionable a las
tres reglas de arriba, porque las tres se sabían y las cuatro veces se incumplieron igual. Lo
que salvó los dos casos que **no** terminaron en un reporte falso no fue acordarse de la regla:

- Arquitectura repitió el `grep` con otro patrón antes de afirmar que Backend no había
  commiteado. No sospechó por prudencia; repitió por costumbre.
- `qa` tenía delante una prueba que pasaba y contradecía su sonda —la del `categoriaId`
  equivocado—, así que fue a leer cómo lo hacía la que funcionaba en vez de reportar el
  defecto. **Cuando una sonda propia dice que algo básico está roto y la suite dice que no, la
  sonda es la sospechosa.**

En los dos casos lo que funcionó fue **una segunda medición**, no tener presente la regla.
Conocer el patrón no protege de repetirlo: Arquitectura lo repitió media hora después de
señalárselo a otro rol.

**Y por eso el correctivo tampoco es "prestar más atención".** Las cuatro veces el error lo encontró **otro
rol**, no quien lo cometió, y ninguno de los cuatro se sentía inseguro al afirmarlo. Lo que
funcionó fue tener alguien mirando con otra pregunta en la cabeza — y que quien se equivocó lo
contara en vez de corregirlo en silencio, porque eso cambia qué va a mirar el siguiente.

---

# Estado del proyecto

## Progreso
- Documentación inicial completa y aprobada: gobernanza, RF, RNF, glosario, actores/permisos, contratos por dominio, modelo de persistencia con plan de migraciones, taxonomía de errores, experiencia e integración de la interfaz, integración con SUNAT y cinco ADR.
- Roadmap del horizonte aprobado: 19 sprints en 9 olas, con matriz de cobertura completa.
- Los 19 RFC redactados y aprobados por el usuario el 2026-08-19. Planificación del horizonte COMPLETA.
- Repositorio publicado en https://github.com/Espiritu16/ventas-inventario
- Ejecución iniciada el 2026-08-19. Los cinco chats de rol están abiertos y conectados por canal directo con el Coordinador.
- **Tres sprints completados**: S-00 (fundación), S-01-B (acceso, usuarios y control de permisos) y S-DO-01 (entorno reproducible). Los tres fusionados en `develop`. Cada uno fue rechazado una vez por QA y aprobado tras corregir.

## Ola 3 — habilitada el 2026-08-19 (reanudación)

**Decisión: S-02-B y S-03-B van en SECUENCIA, no en paralelo.** El roadmap los declara
paralelizables y sigue siendo cierto a nivel de dependencias funcionales, pero
comparten `database/migrations/` y `config/`, así que dos sesiones simultáneas
chocarían en el árbol. Renunciar al paralelo por una razón operativa real es una salida
válida y no contradice el roadmap. Decisión del Coordinador sobre la recomendación
registrada, autorizada por el usuario al ordenar la reanudación.

Habilitados ahora, en dos carriles:

| Carril | Sprint | Rol | Rama | Worktree |
|---|---|---|---|---|
| 1 | **S-02-B** — catálogo: categorías y productos | `implementation-backend` | `sprint/S-02-B` | scratchpad de sesión |
| 2 | **S-01-F** — base de la interfaz y pantalla de acceso | `implementation-frontend` | `sprint/S-01-F` | scratchpad de sesión |

Ambos parten de `develop@b1c7b13`. **S-03-B queda en `PLANIFICADO`** y se habilita al
cerrar S-02-B, en el mismo carril.

### Inventario de estado externo — hecho mirando la máquina

| Recurso | Estado real | Decisión |
|---|---|---|
| PostgreSQL local | Corriendo, conecta con el rol de la aplicación | Base por carril: `ventas_inventario_s02b_test` y `ventas_inventario_s01f_test` |
| Puerto 8000 | Libre | Para quien levante `artisan serve`; se coordina si los dos lo quieren a la vez |
| Puerto 8080 | Libre | Entorno contenerizado, si alguno lo usa |
| Puerto 5173 | Libre | Vite en modo desarrollo, que S-01-F probablemente use |
| Contenedores ajenos | `reservas-canchas-mysql` en 3307 | De otro proyecto; no interfiere |

Nota de método sobre este inventario: `lsof` reportó el 5432 como libre y la base
**sí** estaba corriendo y aceptando conexiones. La comprobación válida fue conectarse,
no consultar un listado de puertos. Es el mismo patrón de configuración divergente
registrado más arriba, esta vez cometido por el Coordinador al inventariar.

`devops` reprodujo la causa y es peor que un descuido: sin privilegios, `lsof` solo ve
los sockets de los procesos propios, y en vez de decir "no puedo ver el resto" devuelve
**salida vacía, sin error y con código de salida normal**. Con `sudo` pediría
contraseña, así que en un script desatendido el resultado sería el mismo silencio.

**Regla que se deriva, y que gobierna los health checks de S-DO-02:** una herramienta
que responde "nada" cuando en realidad quiere decir "no puedo ver" es indistinguible de
una que responde "nada" porque no hay nada. Una comprobación de salud tiene que
**ejercer el servicio** —conectarse, pedir algo, mirar la respuesta— y nunca consultar
un registro sobre él. Y si puede fallar por falta de permisos, tiene que distinguir ese
caso del caso sano, o mentirá exactamente cuando más importa. Es el mismo falso verde
del healthcheck que devolvía 200 sirviendo una advertencia, con otra cara.

## Nota para cuando el barrido de escape bloquee un uso legítimo

`qa` verificó que el barrido de las seis vías de salida cruda **es completo para el
contexto JavaScript**, y por una razón que conviene tener escrita: dentro de un bloque
`<script>` un `{{ }}` no es explotable, porque las entidades HTML no se decodifican ahí
—una carga sale inerte, corrompiendo el dato sin ejecutar—. La única forma de meter
JavaScript ejecutable desde un dato es desactivar el escape explícitamente, y esas seis
vías son exactamente ese conjunto.

**Pero una de ellas, `@js()` / `Js::from()`, es la forma correcta de pasar datos a
JavaScript.** El día que alguien la necesite legítimamente, la prueba lo va a bloquear.
Es defendible —obliga a que ese uso pase por revisión— pero **no es un falso positivo**:
si ocurre, la respuesta es revisar el caso y decidir, no relajar el barrido por reflejo.

## Huecos de cobertura abiertos — S-02-B, aprobados con ellos a la vista

QA aprobó S-02-B y reportó dos huecos que ninguna prueba sostiene. **No son defectos:
el comportamiento hoy es correcto y está verificado.** Lo que falta es lo que impediría
que se rompa sin que nadie se entere.

| # | Qué no está fijado | Consecuencia si se rompe | Propietario |
|---|---|---|---|
| ~~H-1~~ | ~~La rama de Livewire en el middleware de acceso.~~ **CERRADO en S-01-F.** `implementation-frontend` escribió `test_livewire_recibe_el_codigo_y_no_un_redirect` en `tests/Feature/Livewire/`, que es su ruta declarada, y verificó por mutación que es la única de 233 que falla al quitar la rama | — | cerrado |
| H-2 | La clave foránea de categoría en `RESTRICT`. Cambiarla a `CASCADE` no lo detecta nadie | Borrar una categoría arrastraría sus productos. Hoy **ninguna ruta ni método borra categorías**, así que protege contra algo que aún no se puede hacer | `implementation-backend` |

**H-1 quedó cerrado sin necesitar la enmienda**, y el cómo importa: yo lo di por
bloqueado porque supuse que la prueba tenía que vivir en `tests/Feature/Autorizacion/`,
un directorio sin dueño. `implementation-frontend` notó que lo que se protege es el
comportamiento observable **desde el lado de Livewire**, y eso cae en
`tests/Feature/Livewire/`, que sí es su ruta declarada. No hizo falta tocar nada de
backend.

La lección es sobre el bloqueo, no sobre la prueba: **antes de declarar algo bloqueado
por permisos, conviene preguntarse desde qué lado se observa la garantía**, no solo
dónde vive el código que la implementa. Puede haber un dueño legítimo que la suposición
inicial descarta.

**H-2 sigue bloqueado por la enmienda de permisos por área**, junto con la corrección de
la raíz.

QA consideró rechazar por H-1 y explicó por qué no lo hizo, en vez de decidirlo por
omisión: `RECHAZADO` está definido como no conformidad reproducida, regresión,
divergencia de contrato o alcance no aprobado, y un hueco de cobertura no es ninguna de
las cuatro. Estirar la definición para forzar el resultado habría sido peor que
reportarlo y dejar la decisión donde corresponde.

## Decisiones de Arquitectura de la ola 3

**El proyecto no expone una API HTTP entre backend y frontend.** Ya estaba en ADR-0005
y en `docs/frontend/integracion.md`, pero `docs/contratos/usuarios.md` declaraba
endpoints JSON para listar, crear y actualizar usuarios, y S-01-B los implementó
correctamente contra ese contrato. Al llegar S-01-F, la pantalla de usuarios chocó con
esa ruta: dos frentes reclamando la misma URI, uno para una vista y otro para JSON.

Resuelto enmendando el contrato: esas tres rutas son **pantallas**, no endpoints. Los
endpoints JSON se retiran junto con sus pruebas, porque no tienen consumidor previsto
—el frontend invoca `UsuarioService` en el mismo proceso— y una superficie que nadie
usa no se deja abierta. `POST /login` y `POST /logout` siguen siendo HTTP genuinos.

**`GET /login` se declara accesible sin sesión.** Nunca estuvo en la matriz, solo la
operación `POST /login`. Bajo deny-by-default eso produce un catch-22: hace falta
sesión para ver la pantalla donde se obtiene la sesión.

**`GET /panel` en S-01-F es solo el armazón** —layout y menú— sin contenido de negocio.
El tablero con alertas es S-06-F. UT-02 necesita que `/panel` exista como destino tras
iniciar sesión, no que muestre datos.

Las tres son la **tercera, cuarta y quinta instancia** del mismo patrón: un RFC pide un
resultado cuyo artefacto no está declarado, o dos documentos aprobados que no pueden
cumplirse a la vez. Ver la corrección ya aplicada a `project-continuity` sobre rehacer
la auditoría de permisos cuando aparecen los RFC.

## Tensión a resolver antes de S-09-B — no urgente, sí anotada

`AGENTS.md` declara para accesibilidad *"recorrido completo de la venta operable solo
con teclado, verificado de forma automatizada"*. RNF-008, en su sección de cómo se
mide, pide *"recorrido manual documentado que registra una venta completa sin usar el
mouse"*. **No dicen lo mismo**, y hoy nadie tiene que elegir.

Lo vuelve concreto una limitación que QA verificó ejecutándola, no deduciéndola: el
navegador que puede conducir es Chromium 148 embebido en Electron —mismo motor Blink
que Chrome y Edge, distinto contenedor— y **la tecla `Tab` no mueve el foco**: se
intercepta antes de llegar a la página, aunque escribir texto sí funciona. Puede leer
`document.activeElement`, fijar el viewport en 1366x768 y leer el árbol de
accesibilidad, así que el orden de tabulación es verificable **por estructura**, no por
ejecución.

Para S-01-F alcanza: su criterio pide que el foco *vuelva* a un campo, no un recorrido
con teclado. Para S-09-B no: o se decide una herramienta que controle el teclado de
verdad —lo que reabre la decisión de E2E, hoy pospuesta— o se acepta el recorrido
manual documentado que el propio RNF-008 describe. Decidirlo con el sprint encima es
peor que decidirlo ahora.

## Cómo se leen las secciones "Punto de detención"

**Son instantáneas fechadas, no estado vigente.** Cada una describe el proyecto en el momento
exacto en que se paró, y era cierta entonces. **Ninguna se actualiza después**: reescribirlas
falsificaría el registro de qué se sabía en ese momento, que es justamente para lo que sirven.

El estado vigente vive en **un solo sitio**: el bloque `sprints:` legible por máquina al
principio de este documento. Ante cualquier contradicción entre una parada y ese bloque, manda
el bloque — y ante una contradicción entre el bloque y Git, manda Git (Invariante 1).

**Por qué esto necesita estar escrito.** La tercera parada dice que S-02-F tiene "entorno
listo, **sin código escrito**". Era exacto cuando se anotó y hoy es lo contrario: ese sprint
entregó 22 archivos y está en `main`. Con chats humanos alguien recuerda la diferencia; **un
subagente lee lo que el documento dice.** Bajo el modelo de despacho por subagente, una
instantánea sin fechar su alcance es una instrucción equivocada esperando a alguien.

Se declara la regla una vez en lugar de poner un aviso en cada parada: hay cuatro, y la quinta
nacería sin aviso. Es la quinta vez que este proyecto elige declarar el principio en vez de
enumerar los casos.

## Punto de detención — 2026-08-20, tercera parada

Se para con **S-05-B cerrado y una tarea corta pendiente que bloquea a S-05-F**.

| Carril | Estado |
|---|---|
| **S-05-B** | **COMPLETADO** y fusionado. QA aprobó `d51de53` sin rechazo previo |
| **S-02-F** | Rama `sprint/S-02-F` en `e87aded`, entorno listo, **sin código escrito** |
| QA | Sin trabajo asignado |
| DevOps | Sin turno |

### Lo primero al retomar — una tarea corta de `implementation-backend`

**Proyectar el costo fuera de la respuesta del vendedor en `VentaService::encontrar()`**,
y fijarlo con una prueba. El contrato ya está enmendado (`docs/contratos/ventas.md`); falta
el código.

Por qué es lo primero: **debe estar antes de que S-05-F pinte esa pantalla**. Hoy no es
explotable —ADR-0006 retiró el endpoint y ninguna pantalla consume ese método—, así que
este es el mejor momento para cerrarlo y el peor para que se olvide.

QA verificó además que **ninguna prueba lo cubre**: las dos de vendedor en ventas
comprueban el alcance —no ve ajenas, sí ve las propias— y ninguna mira la proyección.

### Después de eso

1. Despachar **S-05-F** (seguimiento de comprobantes y resúmenes diarios) y **S-06-B**
   (emisión electrónica contra beta de SUNAT), que el roadmap declara paralelizables.
2. **Aviso obligatorio antes de S-06-B**, ya vencido en su plazo: hacen falta RUC y razón
   social del emisor, dirección fiscal, usuario secundario SOL y el **certificado digital
   de pruebas**. Todo de ambiente beta. Conseguirlos es trámite; ver "Avisos al usuario".
3. **Segunda promoción a `main`**: `main` tiene la ola 1-3; desde entonces entraron S-02-B,
   S-01-F, S-03-B, S-04-B, S-05-B y toda la gobernanza. Es un lote coherente.

### Lo que S-06-B hereda y no está en su RFC

La verificación de `NIU` contra el ambiente beta, ya enmendada en ese RFC: el primer envío
real debe incluir un ítem con ese código y comprobarse que el CDR lo acepta.

## Punto de detención — 2026-08-20, segunda parada del día

Se para con la **ola 4 a mitad de camino y nada roto**.

| Carril | Estado |
|---|---|
| **S-04-B** | **EN_VALIDACION.** `sprint/S-04-B`, HEAD `5aa956f`, final_sha `4ddac6f`. Sin validar todavía |
| **S-02-F** | Rama `sprint/S-02-F` en `e87aded`, **sin commits propios y sin código escrito**. Entorno instalado y carril `ventas_inventario_s02f_test` creado. El tiempo del sprint se fue en leer antes de implementar — de ahí salió el hallazgo de `CategoriaService` |
| QA | Sin trabajo asignado. S-04-B la espera |
| DevOps | Sin turno |

**Lo primero al retomar:** despachar a QA la validación de S-04-B sobre
`5aa956f2e449726096e791cccca23d0cfc4918b2`, con la gobernanza vigente de ese momento.

**Lo que QA necesita saber y no está en el RFC**, para que no lo levante como
incumplimiento:

- `descontarPorVencimiento()` **no se implementó a propósito** — ver la enmienda del RFC.
- La prueba de `stockDisponible` **cambió de sentido**, no desapareció: ahora fija que el
  catálogo sigue sin devolverlo y que el stock lo sirve el servicio de inventario.
- RNF-003 se demostró con **dos procesos reales**, no con dos llamadas en el mismo
  proceso. Sin el bloqueo de fila, saldo y kardex divergen **respondiendo "ok" las dos
  operaciones**: nada falla, y el daño aparece cuando alguien cuadra el inventario semanas
  después.

**Lo que S-05-B hereda:** escribir `descontarPorVencimiento()` con las reglas de reparto
FEFO, y las dos notas de S-03-B —dirección obligatoria para factura como regla del momento
de emitir, y el tope de S/ 700 como regla de venta—.

**Un dato operativo:** revocar `UPDATE`/`DELETE` sobre el kardex impide borrar lotes, y el
error habla de permisos y no de la clave foránea. En el dominio no importa (ADR-0004: los
lotes no se borran), pero desconcierta a quien lo encuentre.

## Punto de detención — 2026-08-20

Segunda parada, con todo en estado consistente y **una sola acción pendiente del
usuario**. Quien retome no necesita ninguna conversación: todo está acá, en `AGENTS.md` y
en Git.

**Lo único bloqueante: el PR #2** — `gobernanza/enmienda-permisos-por-area` hacia
`develop`. Fusionarlo *es* la aprobación de la enmienda. Detrás de él está todo lo de la
sección "Bloqueantes".

**Lo primero al retomar, en este orden:**

1. Si el PR #2 está fusionado, avisar a `implementation-backend`: commitea el retiro de
   los endpoints de ADR-0006 —hecho en su árbol, sin commitear— y repara las 12 pruebas.
   El diagnóstico línea por línea de las 9 de `RedireccionAlAccesoTest` está en su
   scratchpad; si esa sesión ya no existe, el arreglo es apuntar el data provider y tres
   referencias sueltas a `/usuarios` y `/panel`, que sí son pantallas.
   **Las 12 pruebas son dos problemas distintos, no uno.** Las 9 de
   `RedireccionAlAccesoTest` apuntan a rutas retiradas y hay que cambiarles el destino a
   `/usuarios` y `/panel`. Las 3 de `LivewireOperativoTest` montan `HumoDeInstalacion` sin
   autenticar, y lo que les falta es un usuario con permiso sobre `GET /panel`. Quien las
   trate como un solo problema va a arreglar la mitad. Señalado por `implementation-backend`.

2. Fusionar `feature/permisos-en-componentes`, cuyo HEAD es **`80e682c`** — lleva el
   mecanismo y las tareas de la matriz, y tiene commits que no están en `develop`. Hasta
   que las 12 pruebas estén reparadas, esa rama va en rojo. El mecanismo y el retiro viven
   en un stash con nombre en el árbol de backend, no en commits.
3. `implementation-frontend` saca `HumoDeInstalacion` de la lista de pendientes de
   `DeclaracionDePermisoTest` **cuando la anotación esté en `develop`**, no antes: su
   prueba va a fallar sola pidiéndolo.
4. Abrir S-04-B y S-02-F en paralelo, cada uno con worktree nuevo desde `develop` y su
   base de carril.

**Aviso sobre el orden de S-04-B, si la enmienda todavía no entró.** La prueba
`test_el_listado_no_trae_stock_disponible_todavia`
(`tests/Feature/Autorizacion/RutasDeCatalogoTest.php:140`) fija la ausencia del campo
`stockDisponible`, y S-04-B es el sprint que tiene que hacerla **cambiar de sentido**, no
desaparecer. Vive en un directorio congelado. Si la enmienda no está aprobada cuando ese
sprint llegue a ese punto, **se bloquea a mitad de camino en vez de al principio**, que es
peor porque se descubre tarde y con trabajo ya hecho. Señalado por `qa`; si la enmienda
sigue pendiente, conviene planificar el orden de las unidades contando con esto.

**El PR #1 se fusionó el 2026-08-20.** Primera promoción del proyecto: `main` pasó de
`99cd061` —solo documentación inicial— a `2e735c7`, con los cinco sprints aprobados de las
olas 1 a 3 y toda la gobernanza que produjeron.

`main` no incluye S-03-B ni las decisiones posteriores a la promoción; van en la siguiente,
que se hace en lote cuando el conjunto sea estable, no por sprint.

**Estado de los carriles:** ninguno a medias. Backend tiene trabajo hecho sin commitear a
propósito, para no entregar rojo. Frontend está sin worktree y sin cambios. QA sin nada
pendiente. DevOps sin turno desde S-DO-01.

## Punto de detención — 2026-08-19

El trabajo se detuvo acá por decisión del usuario, con todo en estado consistente.
Quien retome **no necesita esta conversación**: todo lo necesario está en este
documento, en `AGENTS.md` y en Git.

| Qué | Dónde está |
|---|---|
| Trabajo completado | `develop`, publicado en `origin` |
| `main` | Atrás a propósito; la promoción quedó preparada en local, **sin publicar** |
| Worktrees de S-01-B y S-DO-01 | Vivos, con sus sesiones paradas ahí. Eliminarlos solo tras avisar a esos chats |
| Ramas de sprint y de gobernanza | Conservadas para auditoría, todas fusionadas |

**Lo primero al retomar, en este orden:**

1. **Publicar la promoción a `main`** si el usuario la confirma — es la única acción
   pendiente que toca la rama protegida.
2. **Decidir cómo se ejecuta la ola 3**: S-02-B y S-03-B son del mismo rol y hay un
   solo chat de Backend. O se abre un segundo chat, o van en secuencia. Sin esa
   decisión, el paralelismo que el roadmap declara no es realizable. Recomendación
   registrada del Coordinador: secuencia, porque el tiempo ganado con dos chats de
   backend no compensa sumar un tercer carril de contención sobre puertos y bases en
   la primera ola de tres sprints.
3. **Inventariar el estado externo de la ola 3 mirando la máquina**, no razonando
   sobre ella: puertos ocupados, con el entorno contenerizado ya en juego. La base ya
   está resuelta por carril.

**Nada está a medias**: ningún sprint quedó `EN_PROGRESO`, ninguna validación quedó
sin veredicto y ningún documento gobernado quedó afirmando algo que el árbol no
respalde.

### Práctica adoptada — mutación del mecanismo en la validación

**Decisión del Coordinador, 2026-08-19.** En todo sprint que toque autorización,
persistencia o configuración, QA no se limita a comprobar que el mecanismo funciona:
comprueba que **la suite detecta su ausencia**.

Alcance exacto, porque es lo que la hace barata y lo que evita que se lea como otra
cosa: **no es "hacer pruebas de mutación" con una herramienta**. Es romper a mano, una
línea por vez, el mecanismo que ese sprint dice garantizar —quitar una entrada de una
lista de permisos, desactivar un control, invertir una comprobación— y verificar que
alguna prueba falla. Después se restaura el árbol. Once mutaciones costaron minutos en
S-01-B porque cada una era una línea. Redactado como "pruebas de mutación" a secas,
quien lo lea va a pensar en una herramienta y una hora de ejecución, y lo va a saltar.

**La mutación también compara dos versiones de una prueba, no solo prueba contra código.**
Es una extensión de la práctica, adoptada el 2026-08-20 a partir de una observación de
`implementation-frontend`. Cuando se endurece una prueba que ya estaba en verde, **la mejora
no se ve corriendo la suite**: las dos versiones dan verde, porque el defecto que la nueva
cubre todavía no existe. Lo único que las distingue es romper algo que la vieja **no** cubría.

Lo demostró al derivar de la matriz la prueba del menú: coló una sección nueva visible para
todos, y la versión con la lista escrita a mano **habría pasado en verde** porque nadie la
había agregado a la lista, mientras la derivada falla. Sin esa mutación, "endurecí la prueba"
habría sido una afirmación sin respaldo — y una que suena bien.

De ahí la regla de método que se lleva al escribir pruebas nuevas: **preguntarse antes qué
mutación distinguiría la versión buena de la mediocre**, en vez de escribir la prueba y
después buscar cómo verificarla. Si no se puede nombrar esa mutación, probablemente las dos
versiones sean la misma.

**Una mutación demasiado destructiva no informa nada.** Si tumba media suite, dice "algo
se rompió", no "esta regla está protegida". A `implementation-backend` le pasó en S-03-B:
quitar un `CHECK` rompió la migración entera y cayeron 216 de 252 pruebas. Una mutación
útil hace fallar las pruebas que cubren esa regla y pocas más; un número enorme es
señal de que la mutación estaba mal elegida, no de que la cobertura sea excelente.

**Si la mutación no hace fallar nada, averiguá por qué antes de darlo por cubierto o por
descubierto.** Puede ser un hueco de cobertura, pero también puede ser que la garantía
la sostenga otra cosa y el documento diga un motivo equivocado. En S-03-B mutar un
índice único no hizo fallar ninguna prueba, y la causa no era falta de cobertura: el
comportamiento se cumplía por una propiedad del motor, no por la decisión del modelo. El
resultado fue corregir el documento y agregar una prueba **estructural**, porque ninguna
de comportamiento podía distinguir las dos cosas.

**Confirmar que la mutación se aplicó antes de correr la suite.** Una mutación que no
llegó a tocar el archivo se lee como cobertura ausente, y el resultado es un hueco
inventado. Le pasó a `qa` en S-02-B: intentó anular un `CHECK` buscando `->check()`
cuando la migración lo declara con `DB::statement`, el patrón no coincidió, el archivo
quedó igual y la suite pasó — lo que parecía decir que nadie protegía esa regla. Lo
detectó verificando el archivo. Es el reverso exacto de la regla de abajo: una deja
falsa confianza, la otra deja falsa alarma, y las dos se evitan mirando el árbol.

**Después de restaurar, verificar qué se restauró — no qué se pretendía restaurar.** El
`git checkout` de la restauración se lleva todo lo pendiente en ese archivo, no solo la
mutación. `implementation-backend` asumió que se había llevado un cambio y se había
llevado dos: repuso las tres filas de la matriz y no el método que la prueba necesitaba,
y commiteó con la suite en rojo. Contar los cambios pendientes antes de mutar habría
bastado.

Lo notable es quién lo cometió: **el mismo rol que había formulado la regla de mutar
sobre árbol limpio, dos días antes**. Su lectura, que comparto: si una regla de disciplina
falla en manos de quien la escribió y la tenía presente, el problema no es la atención
—es que la disciplina no es el lugar correcto para eso—. Es el mismo argumento con el que
se eligió el hook global sobre la clase base.

**Mutar solo sobre árbol limpio.** La restauración es un `git checkout` del archivo
mutado, y eso se lleva cualquier trabajo sin commitear que hubiera ahí. `implementation-backend`
lo vivió en S-02-B: mutó antes de commitear la corrección, restauró, y quedó con un
commit que tenía las pruebas nuevas y el código viejo. Lo detectó al correr la suite
completa **después** del commit, no antes. El resultado de mutar sobre árbol sucio es
peor que no mutar, porque deja la falsa confianza de haber verificado algo.

Por qué se adopta: en S-01-B encontró una prueba que pasaba **por accidente** —daba el
resultado esperado sin que el control se ejecutara— y protegió tres correcciones de
degradarse en silencio. En la ola 3 hay tres carriles tocando el mismo control de
acceso, que es justo donde una prueba que ya no comprueba nada pasa inadvertida.

Queda escrito acá, y no solo en los despachos, porque un despacho vive en un canal
entre chats y el canal desaparece. Que la práctica dependiera de que el Coordinador se
acuerde de pedirla o de que QA la aplique por criterio propio es exactamente lo que
esta sesión rechazó dos veces. Lo señaló `qa` al verificar que la decisión no estaba en
el repositorio.

Pendiente asociado: incorporarla también al bloque `qa` de `AGENTS.md`, para que sea
obligación del rol y no una decisión registrada. Va junto con la enmienda de abajo, en
la misma aprobación del usuario.

### Inconsistencia conocida, con su causa — pendiente de una decisión del usuario

Los tres handoffs (`S-00`, `S-01-B`, `S-DO-01`) declaran `status: EN_VALIDACION`
mientras este documento los registra como `COMPLETADO`. **No es un descuido: es un
hueco de gobernanza.**

Cada implementador dejó ese campo en `EN_VALIDACION`, que era lo correcto en su
momento — la skill le prohíbe declararse `COMPLETADO` a sí mismo. Cerrar un sprint es
atribución exclusiva del Coordinador. Pero `AGENTS.md` le permite al rol `coordinacion`
escribir en `docs/handoffs/` **solo las secciones de resultados QA/DevOps**, y el
`status` es cabecera del handoff, no esa sección.

Resultado: nadie tiene autoridad para mover ese campo a `COMPLETADO`. El implementador
no puede porque no cierra sprints; el Coordinador no puede porque no es su ruta. El
campo queda congelado en el último valor legítimo que alguien pudo escribir.

Hoy no engaña a nadie porque este documento manda y lo dice claro, pero quien mañana
reconstruya y abra primero el handoff —lo natural para entender un sprint— leerá que
sigue en validación.

**Enmienda propuesta a `AGENTS.md`, pendiente de aprobación del usuario**, en el bloque
`coordinacion`, reemplazando la restricción actual sobre handoffs:

```
- Puede escribir en `docs/handoffs/`: las secciones de resultados QA/DevOps y el
  campo `status` de la cabecera al cerrar el sprint. El resto del handoff es del rol
  que lo ejecutó. Cerrar un sprint es atribución del Coordinador, así que registrar
  ese cierre en el handoff también lo es.
```

Es la corrección mínima: no le abre al Coordinador el handoff entero, solo el campo
que su propia autoridad de cierre ya implica. Detectado por `devops` al verificar el
cierre de S-DO-01, y confirmado por el Coordinador en los tres handoffs.

### Volúmenes de Docker atados a la decisión de los worktrees

Quedan `s-do-01_datos_postgres` y `s-do-01_node_modules`. **Hoy no son huérfanos**: los
reclama el compose si se levanta desde ese worktree. Si mañana se elimina el worktree,
se vuelven huérfanos y con un nombre que ya no corresponde a ningún directorio, así que
nadie los reconocerá — el mismo caso de los tres que se barrieron en S-DO-01. Van
atados a esa decisión, no aparte: si el worktree se va, `docker volume rm
s-do-01_datos_postgres s-do-01_node_modules` va con él.

QA también dejó montados su checkout y la base `ventas_inventario_qa_test` en el
scratchpad de sesión. Son desechables; quien los necesite limpios puede borrarlos sin
consultar.

## Decisiones tomadas durante la ejecución

| # | Fecha | Qué se decidió | Quién lo aprobó | Dónde quedó |
|---|---|---|---|---|
| 1 | 2026-08-19 | Política de ramas de dos niveles: `sprint/<id>` → `develop` → `main`. Antes se integraba directo a la protegida | Kevin Espíritu | `AGENTS.md`, secciones "Política de ramas" y "CI por rama" |
| 2 | 2026-08-19 | La instalación de Livewire se ubica en S-01-B como UT-06. Ningún RFC del horizonte la declaraba, pese a que la gobernanza, ADR-0005 y los contratos la dan por existente | Kevin Espíritu | `docs/rfcs/S-01-B.md`, enmienda y UT-06 |

## S-00 — COMPLETADO (2026-08-19)

QA emitió **APROBADO** sobre `ventas-inventario@f65efca` con gobernanza
`develop@c5c5389`, tras un rechazo previo y su corrección. Fusionado a `develop` en
`4e6af0e`. Rama `sprint/S-00` conservada para auditoría.

Los cuatro comandos de verificación, las cifras y las versiones fueron reproducidos
por QA desde un checkout aislado, no aceptados del handoff.

Lo que este sprint deja, más allá del andamiaje: **dos mecanismos que nacieron de
fallos reales, encontrados por pruebas y no por revisión.**

1. Los instantes se escriben con su desplazamiento horario. La configuración de la
   conexión en UTC era necesaria pero no suficiente: Laravel enviaba las fechas sin
   zona y PostgreSQL las interpretaba como si ya fueran UTC, corriendo cada instante
   cinco horas. Nada fallaba; solo quedaba mal. QA lo confirmó por contrafáctico,
   revirtiendo el mecanismo y viendo reaparecer el corrimiento exacto. RNF-006 se
   cumple por mecanismo, no por una prueba que lo compense.
2. La suite aborta si la base a la que **efectivamente** se conectó no termina en
   `_test`, preguntándole el nombre al motor. La primera versión leía la
   configuración y se eludía por completo vía `DB_URL` — el caso que un CI o un
   contenedor producen de forma natural. Habría permitido destruir la base de
   aplicación con `migrate:fresh` y quedar en verde.

Observaciones informativas registradas por QA, ninguna accionable hoy:

- **OBS-A**: el guardián de arquitectura es una heurística por nombre y no ve
  `VentaRepositorioInterface` ni una clase sin sufijo como `CalculadoraDeIgv`. Es un
  límite inherente, no un defecto. No leerlo como garantía total.
- **OBS-B**: si `DB_CONNECTION` apuntara a un motor sin `current_database()`, la
  salvaguarda muere con error SQL en vez de su mensaje. Falla cerrado, que es lo
  correcto; solo el diagnóstico sería menos claro.
- **QA-05 confirmado empíricamente**: el tamaño del CSS depende del caché de vistas
  compiladas (`storage/framework/views`), no del código. Un build limpio da la cifra
  menor. Que nadie lo lea como regresión en S-DO-02.
- Sin verificar de forma independiente: el esqueleto `laravel/laravel v13.10.0`. El
  framework v13.26.1 sí. Es dato de proceso, no de producto.

**Riesgo residual para S-DO-01/S-DO-02, no para S-00**: `APP_DEBUG=true`,
`APP_ENV=local`, `SESSION_SECURE_COOKIE` sin definir y `SESSION_ENCRYPT=false` son
defaults del esqueleto. Deben endurecerse antes de que exista un entorno servido.

## Validación de S-00 — primer intento, RECHAZADO (2026-08-19)

QA validó `ventas-inventario@58a4c54` contra `develop@b746373`, en checkout aislado
fuera del árbol compartido, con `seguridad-validacion` activada en modo dirigido.
Veredicto **RECHAZADO** por un único defecto bloqueante. Las cinco unidades del RFC
cumplen su criterio literal y RNF-006 quedó confirmado por mecanismo: QA leyó el
instante desde el motor en cinco zonas horarias y comprobó por contrafáctico que,
al revertir la grammar, el corrimiento de cinco horas reaparece exacto.

| ID | Qué falla | Severidad | Propietario |
|---|---|---|---|
| QA-01 / SEG-01 | La salvaguarda de `tests/TestCase.php` se elude vía `DB_URL`: examina el campo de configuración, no el nombre efectivo que resuelve el driver. Con `DB_URL` presente valida un nombre y conecta a otro, así que `migrate:fresh` podría destruir la base de aplicación con la suite en verde. `phpunit.xml` intenta neutralizarlo pero PHPUnit no pisa una variable ya presente sin `force="true"` | **Bloqueante**, alta | `implementation-backend` |
| QA-02 | La prueba de migrate/rollback deja la base de pruebas desmantelada al terminar la suite | No bloqueante | `implementation-backend` |
| QA-03 | El detector de arquitectura de UT-04 reconoce los sufijos `Service`/`Repository` en inglés; con la nomenclatura española del proyecto no ve `VentaServicio` ni `ProductoRepositorio`. Queda ciego cuando S-01-B cree los primeros servicios | No bloqueante | `implementation-backend` |
| QA-04 | El README no menciona `pnpm build`; seguirlo al pie de la letra da 500 por falta de manifiesto de Vite | No bloqueante | Coordinación — **corregido** en esta misma enmienda |
| QA-05 | Diferencia de tamaño del CSS entre el handoff y un build limpio. No es defecto: `@source` sobre un caché no versionado del esqueleto | Informativo | — |

Riesgo residual anotado para S-DO-01/S-DO-02, no para S-00: `APP_DEBUG=true`,
`APP_ENV=local`, `SESSION_SECURE_COOKIE` sin definir y `SESSION_ENCRYPT=false` son
defaults del esqueleto y deben endurecerse antes de que exista un entorno servido.

Higiene de secretos verificada como buena: ningún `.env`, certificado ni clave
versionado en todo el rango del sprint; `composer audit` y `pnpm audit` sin
advisories.

## Aislamiento de base por carril — decidido antes de la ola 3

El usuario otorgó `CREATEDB` al rol `ventas_inventario` el 2026-08-19 (verificado:
`rolcreatedb = t`). Con eso, cada carril paralelo puede crear su propia base de
pruebas y la serialización por turnos deja de ser necesaria. Los turnos manuales
funcionaron con un carril; con tres no escalan.

**Convención de nombre — el orden de las partes no es estético:**

```
ventas_inventario_<carril>_test
```

Ejemplos: `ventas_inventario_s01f_test`, `ventas_inventario_s02b_test`,
`ventas_inventario_s03b_test`.

El sufijo `_test` va **al final, siempre**. La salvaguarda que S-00 dejó en
`tests/TestCase.php` aborta si el nombre efectivo de la base no termina en `_test`,
así que un nombre como `ventas_inventario_test_s02b` —que es el orden que sale
natural al escribirlo— sería rechazado por la propia protección y el carril no
podría correr sus pruebas. La protección funcionaría exactamente como debe; lo que
estaría mal es el nombre.

Reglas de uso:

- Cada carril fija su base en el `.env` de **su propio worktree**, que no se versiona.
  Ningún carril toca la base de otro.
- `ventas_inventario_test` queda como la base por defecto de quien trabaje sin
  paralelismo. No es de nadie en particular.
- `ventas_inventario` es la base de aplicación y ninguna suite la toca jamás. Esa es
  precisamente la garantía que la salvaguarda existe para dar.
- El carril crea su base al empezar y puede dejarla al terminar; no se exige
  limpiarla, porque `RefreshDatabase` la recompone y su nombre dice a qué sprint
  pertenece.

Esto no reemplaza el inventario de estado externo que hay que hacer en cada ola: la
base era un recurso compartido, no el único. Puertos, caché y directorios temporales
se siguen inventariando por ola, mirando la máquina y no razonando sobre ella.

**Dentro del entorno contenerizado esta convención no hace falta**: el usuario del
contenedor es dueño de su propio clúster y puede crear y borrar bases, y cada copia
del repositorio levanta su propio Compose con volúmenes separados, así que dos
carriles en contenedores no comparten base ni aunque usaran el mismo nombre.
Verificado por `devops` creando y eliminando una base de carril, no deducido. La
convención de arriba sigue rigiendo para quien trabaje contra la instalación local.

**Un puerto publicado es estado externo compartido, y ningún aislamiento de Compose
lo cubre.** El nombre de proyecto derivado del directorio separa contenedores,
redes y volúmenes; el puerto del host queda fuera de ese perímetro por definición,
porque publicar es exactamente exponerlo a la máquina. En S-DO-01 esto aplica solo
al 8080 —la base no publica ninguno, por decisión—, y el riesgo grave no es que un
segundo entorno falle al levantar, que sería ruidoso: es que el puerto responda con
**otro** entorno mientras quien prueba cree estar viendo el suyo. Eso no falla,
aprueba, y aprueba lo que no era. Si alguna vez hacen falta dos entornos a la vez en
la misma máquina, la salida conocida es parametrizar el puerto publicado
(`${PUERTO_APP:-8080}:8000`); no se implementó porque hoy ningún caso lo pide.

## Lo que no tiene verificación automática es lo que falla

El 2026-08-20 fallaron dos cosas, y no fueron el código ni los sprints: **el
procedimiento de aprobación** —se pidió aprobar un texto que no existía como documento— y
**el canal entre sesiones** —se trasladó un "ya está hecho" que vivía en una rama sin
fusionar—.

No es casualidad. Todo lo demás en este proyecto tiene algo detrás que lo respalda: una
prueba, un SHA, un documento versionado. Esas dos partes no tienen nada salvo que alguien
se acuerde de seguirlas. **Son las dos únicas sin verificación automática, y son las dos
que fallaron.** Las dos veces el error apareció recién cuando alguien fue a mirar.

La observación es de `implementation-frontend`, que fue quien miró las dos veces.

**La otra mitad, que señaló `implementation-backend`:** el error se encontró porque
alguien fue a buscar el artefacto en vez de confiar en la afirmación. Lo que lo atrapó no
fue que quien se equivocó se diera cuenta, fue que otro verificó — el mismo mecanismo que
funcionó cuando QA encontró pruebas que pasaban por la razón equivocada. **La verificación
cruzada entre roles es lo único que cubre las partes que no tienen prueba.**

**Y una asimetría que conviene tener presente**, también suya: los errores que uno comete
sobre sus propias reglas no son distintos de los demás, pero **se sienten peores, y por eso
dan la tentación de no reportarlos**. Reportarlos rápido es lo que hace que la regla
siguiente se escriba mejor: la de "verificar qué se restauró" no existiría si él hubiera
corregido el commit en silencio.

**Corolario, de `devops`:** los huecos que no molestan a nadie son los que sobreviven. El
suyo con `README.md` se notó porque lo bloqueaba; el de `docker/` no se notó durante un
sprint entero porque no lo bloqueaba.

Del canal ya salió una regla concreta —referenciar por SHA fusionado, abajo—. Del
procedimiento de aprobación salió que el borrador viva en una rama antes de pedir la
aprobación, en vez de en un mensaje. Ninguna de las dos es una verificación automática:
siguen dependiendo de que alguien las siga. **Queda anotado como cosa a pensar, no como
resuelto** — vale la pena discutir con el usuario si hay forma de que fallen solas en vez
de esperar a que alguien vaya a mirar.

## Regla de despacho — un trabajo ajeno se referencia por SHA fusionado, nunca por "ya está hecho"

Cuando el Coordinador despacha una tarea que depende del trabajo de otro rol, **indica el
SHA donde ese trabajo está fusionado en la rama compartida**. No alcanza con trasladar que
el otro rol dijo haberlo hecho.

Entre "lo hice" y "está en `develop`" hay una distancia que nadie mide si no se nombra: el
trabajo puede estar en una rama sin fusionar, en un commit posterior al que se citó, o sin
commitear. Un "ya está hecho" sin SHA es una afirmación sin evidencia, exactamente igual
que un handoff que declara `COMPLETADO` sin `final_sha`.

Ocurrió dos veces, las dos por el mismo canal y las dos las atrapó
`implementation-frontend` verificando antes de tocar:

1. Se le indicó traer `77fed0f` como "ya corregido"; la corrección estaba en `03a54ae`,
   posterior. Su rama quedó cargando el defecto sin haber tocado nada.
2. Se le pidió actualizar una lista porque un componente "ya estaba anotado"; la anotación
   vivía en una rama sin fusionar. Hacer el cambio habría roto `develop`.

Lo notable es dónde nace el defecto: **no en el código sino en el canal entre sesiones**,
que es la única parte del sistema que no tiene pruebas. La regla es del Coordinador porque
el SHA es un dato que él tiene a mano y quien recibe la tarea no.

## Regla de permisos — "lo escribí yo" no es "es mi ruta"

Un rol es dueño de las rutas que `AGENTS.md` le declara, **no de los archivos que
escribió primero**. Si valiera la autoría, cada rol sería dueño de lo que tocó antes que
nadie y la partición dejaría de significar algo: dos roles podrían reclamar el mismo
directorio según quién llegó primero, que es exactamente lo que la partición existe para
evitar.

Es la confusión que produjo el inventario de los 32 archivos sin dueño: buena parte los
escribió `implementation-backend` durante tres sprints, y eso no los volvió suyos.

La distinción importa sobre todo cuando la salida cómoda es tentadora. Lo señaló él mismo
al quedar bloqueado por un archivo que había escrito dos días antes, en el sprint en
curso: **"se siente mío, pero se siente no es lo mismo que está declarado"**. Reconocerlo
ahí, y no cuando el archivo es ajeno, es lo que hace que la regla sirva.

## Patrón recurrente — dos valores que hay que mantener iguales

Ocho veces ya. Las que se pudieron cerrar se cerraron igual: **reemplazar dos fuentes que
alguien debe mantener sincronizadas por una sola, derivada de donde nace el dato.** Cuando la
duplicación no se puede eliminar —porque una de las dos fuentes es un documento que la gente
lee— se cierra con lo segundo mejor: **una prueba que falla cuando divergen.**

1. **El prefijo de Livewire.** Se iba a fijar por configuración y declarar la cadena en
   la gobernanza. Se deriva de `APP_KEY`, así que la declaración habría sido correcta en
   una máquina y falsa en todas las demás. Se resolvió leyéndolo de la misma fuente que
   registra las rutas.
2. **El nombre del componente de acceso.** Se declara por la clase y el nombre de
   invocación se deriva del registro de componentes, no de una cadena escrita a mano.
3. **El coste del señuelo de bcrypt.** Es una constante precalculada con coste 12
   mientras los hashes reales usan el configurado; hoy coinciden por casualidad.
   Pendiente en S-08-B: derivarlo del coste vigente.
4. **La comprobación de permiso en `mount()` y en `render()`.** Se retiró la del montaje
   en lugar de dejar ambas.
5. **La matriz de permisos y su transcripción a código.** `docs/requisitos/actores-permisos.md`
   es la autoridad y `app/Compartido/Autorizacion/MatrizDePermisos.php` se escribe a mano
   desde ella. **Es la instancia más grave de todas**, porque el código es el que decide y
   el documento el que se lee: cuando divergen, la gobernanza dice una cosa y el sistema
   hace otra, sin que nada falle.

   Ocurrió el 2026-08-19: Arquitectura corrigió tres filas del vendedor en el documento y
   no despachó el cambio de código. Durante ese lapso, quien construyera la pantalla de
   catálogo habría dado acceso al vendedor **derivándolo correctamente de la fuente que la
   gobernanza le indica usar**. Lo encontró `implementation-frontend` leyendo las dos.

   **Decisión, 2026-08-19: una prueba verifica que el código coincide con el documento.**
   Vive en `tests/Unit/`, ruta ya declarada de `implementation-backend`. Lee la tabla de
   `actores-permisos.md` y la compara fila por fila con `MatrizDePermisos`. No convierte
   al documento en generador —sigue escribiéndose a mano— pero **la divergencia deja de ser
   silenciosa**, que es lo único que hacía falta. Es el mismo criterio con el que se
   resolvieron las cuatro anteriores, aplicado al caso donde más costaba.

6. **El contrato de servicios de dominio y las firmas reales.** `docs/contratos/servicios-de-dominio.md`
   es la interfaz completa entre los dos frentes y **nadie lo actualizó en cinco sprints**.
   Declaraba siete métodos que no existen y omitía cuatro servicios enteros. Sobrevivió tanto
   porque hasta S-02-F ningún sprint de frontend consumió un servicio: QA validó cada sprint
   contra su RFC y su contrato por dominio, y este documento no entraba en ninguna de esas
   comparaciones. **Ninguna divergencia se detecta por casualidad; se detecta porque algo la
   compara.**

   Sincronizado hacia el código el 2026-08-20 en `a188e74`. Cerrado con el mismo criterio que
   la quinta: una prueba de consistencia asignada a S-02-F que compara documento y métodos
   públicos reales **en las dos direcciones** —método sin declarar y declaración sin método—,
   porque acá el problema es de los dos tipos a la vez y mirar una sola dirección deja pasar
   la mitad. Lo observó `qa`. Para que esa prueba no sea frágil, el documento pasó a declarar
   cada firma en una fila con formato fijo y una columna de estado; el aviso de que una prueba
   sobre prosa se pone roja sin que nada esté mal también es de `qa`.

   Y una segunda corrección de `qa`, sobre un criterio que yo ya había dado por bueno:
   reconocer las tablas de servicio **por su encabezado**, no por contar sus columnas. Contar
   columnas funciona hoy y falla en silencio el día que una tabla gane una — la tabla deja de
   ser reconocida, sus métodos salen de la comparación y **la prueba sigue en verde**. Se suma
   una guarda que afirma cuántas tablas encontró, por el mismo principio que la comprobación
   de coherencia de la sonda de concurrencia: el instrumento verifica que midió lo que cree
   haber medido. **Un criterio que funciona por una propiedad accidental del documento no es
   más seguro que no tener criterio; es menos, porque parece que lo tiene.**
7. **La traducción de reglas de validación a códigos de error.** `ValidadorDeDominio` la hace
   para todos los dominios y `UsuarioService` **tiene su propia copia privada**, escrita en
   S-01-B antes de que existiera la compartida. Las dos ya divergieron: la compartida trata
   `Between` y la copia no. Es la primera instancia del patrón **dentro del código de
   producción**, no entre código y documento — y por eso es la que menos excusa tiene: acá la
   duplicación sí se puede eliminar del todo. Pendiente de despacho a `implementation-backend`
   junto con el retiro del código por defecto de `'Unique'` (`c8e9771`).

8. **La lista de secciones del menú en su prueba, contra la matriz de permisos.**
   `FundacionDeInterfazTest::test_el_vendedor_solo_ve_las_secciones_que_le_corresponden`
   recorre `['Usuarios', 'Categorías', 'Productos', 'Proveedores']` **escrita a mano**. Cuando
   aparezca una sección que el vendedor no deba ver, alguien tiene que acordarse de agregarla
   ahí; si no lo hace, **la prueba sigue verde sin cubrirla**.

   Lo encontró `qa` al validar S-02-F, y tiene un origen que vale registrar: nació de reparar
   otra prueba que había caducado por el mismo motivo —afirmaba que el menú del vendedor está
   vacío, y dejó de ser cierto al aparecer Clientes—. **La reparación cambió una foto por otra
   foto**: en vez de "no ve nada" quedó "no ve estas cuatro". Más precisa, igual de perecedera.

   Lo cierra hacer lo que el menú ya hace: derivar las secciones esperadas de
   `MatrizDePermisos` en vez de enumerarlas. Entonces la prueba comprueba la regla y no la foto
   de las pantallas de hoy, y una sección nueva queda cubierta el día que se declara.

   **La lección de método es la que menos se ve:** reparar una prueba que caducó no garantiza
   haber quitado lo que la hacía caducar. Hay que preguntar de qué depende la afirmación nueva,
   no solo si hoy es cierta.

Sobre la cuarta, el argumento que la cierra es de `qa` y es más fuerte que "no agregaba
mucha cobertura": **`mount()` corre una vez y siempre antes de un `render()`, así que su
conjunto de casos es un subconjunto estricto del de `render()`**. No agrega ninguno.
Dejarla sería un segundo lugar que mantener a cambio de cero casos nuevos.

**Cuándo sí vale tener dos capas:** cuando son independientes y fallan por causas
distintas. Eso ya existe acá — el componente comprueba el permiso y el servicio no
confía en el componente. Dos comprobaciones idénticas dentro del mismo objeto, una
contenida en la otra, no son dos capas: son una escrita dos veces.

## Patrón recurrente — la configuración declarada y la conexión real divergen

Ha aparecido **tres veces, por caminos distintos**, y se registra como patrón para
que la cuarta se reconozca antes de costar una validación:

1. **S-00** — la salvaguarda de la suite leía `config(...database)`, pero `DB_URL`
   pisa los campos sueltos. Validaba un nombre y conectaba a otro.
2. **S-DO-01** — `php artisan serve` reinyecta el `.env` en el proceso servido y
   pisaba la configuración del contenedor. `tinker` conectaba bien mientras el
   navegador fallaba.
3. **S-01-B** — `validated()` devuelve el valor tal como llegó, sin castear, así que
   la guarda comparaba contra una forma del dato y el modelo persistía otra.

4. **S-DO-01, dos veces más** — el compose definía `APP_ENV` y `QUEUE_CONNECTION`
   como variables reales del entorno, y esas ganan sobre el bloque `<env>` de
   `phpunit.xml`. Sin `APP_ENV=testing` fallaban 17 pruebas por verificación CSRF;
   con `QUEUE_CONNECTION` pisado el efecto era **silencioso**: las pruebas encolaban
   de verdad en lugar de ejecutar en el acto. Es el mismo mecanismo que ya se
   conocía por `DB_DATABASE`, en dos variables que nadie había mirado.

La forma común: **existe un valor declarado y un valor efectivo, y el código
confía en el declarado.** El síntoma siempre aparece lejos de la causa, y en todos
los casos hubo una prueba en verde que no lo detectaba.

Frontera que quedó escrita en el compose a raíz del cuarto caso, y que conviene
respetar en cualquier entorno futuro: **el compose define dónde está el servidor de
base; `phpunit.xml` define cuál base y en qué modo corre la aplicación.** Una
variable que cruce esa frontera pisa a la otra sin avisar.

Regla que se deriva de esto, aplicable a cualquier sprint: cuando una decisión
dependa de un valor de configuración, preguntar por el valor **efectivo** a quien
realmente lo determina —el motor, el driver, el modelo— en vez de leer el declarado.
La corrección de S-00 con `select current_database()` es el ejemplo de referencia.

## Ola 2 en curso — S-01-B rechazado en su primera validación (2026-08-19)

QA rechazó `b4fbc35` por dos defectos bloqueantes, ambos acotados y ninguno
estructural. El mecanismo de control de acceso quedó verificado a fondo: QA corrió
ocho mutaciones sobre él —quitar rutas de las listas, quitar el middleware global,
hacer que la autorización acepte siempre, abrir el patrón de assets a cualquier
método— y la suite detectó las ocho.

| ID | Qué falla | Severidad |
|---|---|---|
| QA-01 | Un administrador puede desactivarse a sí mismo enviando `activo: 0` o `"0"`. La guarda compara en estricto contra booleano y `validated()` devuelve el valor sin castear. Deja el sistema **sin administrador activo y sin forma de recuperarlo desde la aplicación**. `"0"` es lo que envía un checkbox de HTML, o sea el camino normal de la pantalla que construirá S-01-F | **Bloqueante**, alto |
| QA-02 / SEG-01 | El login distingue por tiempo un correo inexistente: 392 ms contra 191 ms, razón 2.05x, medible por red. La mitigación introdujo la señal que quería borrar, porque genera el hash señuelo dentro de la petición y ejecuta dos bcrypt en vez de uno. Permite enumerar usuarios | **Bloqueante**, medio |
| QA-03 | La lista de componentes accesibles sin sesión quedó vacía. **Error de secuencia mío**: fijé el nombre de la clase después de que Backend fijara su `final_sha` | No bloqueante |
| QA-04 | Un comentario del código repite el dato sobre `livewire.min.js` que ya se corrigió en la gobernanza | No bloqueante |

**SEG-02** — ver la enmienda de `docs/rfcs/S-08-B.md`.

Método a tener en cuenta en S-DO-02: la suite Feature exige que `pnpm build` haya
corrido antes, o cuatro pruebas fallan por falta del manifiesto de Vite. El orden de
los pasos del workflow importa.

## Entradas obligatorias para S-DO-02

Se registran acá, y no solo en el handoff de S-DO-01, porque son condiciones que
S-DO-02 debe cumplir y su RFC todavía no las declara.

- **Endurecimiento de configuración para el entorno servido**: `APP_ENV`,
  `APP_DEBUG`, `SESSION_SECURE_COOKIE` y `SESSION_ENCRYPT`. Lo levantó QA como riesgo
  residual al validar S-00. DevOps lo ubicó en S-DO-02 y no en S-DO-01, con este
  razonamiento, que acepto: en un entorno local de desarrollo `APP_DEBUG=true` y
  `APP_ENV=local` son lo correcto, no un defecto — forzarlos a `production` dentro
  del compose de desarrollo empeoraría el entorno sin proteger nada. `SESSION_SECURE_COOKIE`
  exige HTTPS y `SESSION_ENCRYPT` supone sesiones reales de usuarios; ninguna de las
  dos condiciones existe hoy. Lo que vuelve exigible el endurecimiento es
  precisamente el despliegue, que el RFC de S-DO-01 declara fuera de alcance.
- **No heredar la credencial local del compose.** La contraseña de la base
  contenerizada de S-DO-01 es un literal en `docker-compose.yml`, aceptable ahí
  porque el puerto no se publica y no da acceso a nada real. El compose de un entorno
  servido no puede heredar ese patrón.

## Pendientes de planificación

- ~~**Estado externo compartido en la ola 3.**~~ **RESUELTO el 2026-08-19** — ver "Aislamiento de base por carril" abajo. Lo detectó el chat de Frontend antes de que costara nada.
- **Árbol de trabajo único.** Las cinco sesiones comparten `/Users/sankef/ventas-inventario`. Hoy funciona porque S-00 corre solo, pero cualquier ola con dos sprints simultáneos exige worktrees dedicados por carril, acordados antes del despacho.
- **Configurar series de comprobante no tiene sprint — decisión de alcance pendiente del usuario, escalada el 2026-08-20.** RF-014 tiene dos mitades: *asignar* el correlativo, hecha en S-05-B (`SerieComprobanteService::reservarCorrelativo`), y **que el administrador configure la serie**, que no la implementa ningún RFC, no tiene pantalla en `docs/frontend/experiencia.md` y sí tiene dos filas en la matriz de permisos (`GET`/`POST /series-comprobante`, ambas `Sí` para administrador).

  Hoy no se nota porque las pruebas insertan la serie directamente en la base. **Se nota en S-06-B**, el primer sprint que emite de verdad: sin una serie configurada, `registrar` rechaza con `SERIE_NO_CONFIGURADA` antes de llegar a SUNAT, y no hay forma soportada de crearla desde el sistema.

  Apareció al sincronizar el contrato de servicios (`a188e74`) — no lo encontró una revisión de RF-014, lo encontró comparar el documento con el código. Es exactamente lo que predice la sexta instancia del patrón de dos fuentes: **la mitad no implementada de un requisito no se ve en ningún artefacto que alguien lea de corrido**; se ve cuando dos artefactos se comparan.

  No lo resuelve el Coordinador: cambiar el alcance de un sprint o abrir uno nuevo es decisión del usuario. Mientras tanto, `SERIE_DUPLICADA` tampoco existe en la taxonomía de errores, porque su método no tiene dónde vivir.

## Bloqueantes

**Ninguno. La enmienda de permisos por área se aprobó el 2026-08-20** al fusionar el PR
#2, y con ella se destrabaron los cinco directorios de prueba que no tenían dueño desde
S-00 —`tests/Feature/Autorizacion/`, `tests/Feature/Fundacion/`, `tests/Feature/Interfaz/`,
`tests/Soporte/` y `tests/recursos/`—, las doce pruebas que nadie podía reparar, el retiro
de endpoints de ADR-0006, el mecanismo de permisos en componentes y los sprints S-04-B y
S-02-F.

Esa enmienda estuvo bloqueando trabajo real durante cinco escaladas sucesivas. Vale
recordar por qué: `AGENTS.md` declaraba permisos como lista de rutas conocidas al
aprobarlo, y cada sprint materializaba artefactos que la lista no anticipaba. Ahora declara
áreas, así que un directorio nuevo no queda sin dueño por el solo hecho de ser nuevo.

- Sin bloqueo para el resto de la planificación. S-06-B se desarrolla y S-QA-01 valida contra el ambiente **beta**, con credenciales y certificado de prueba: no hacen falta datos del negocio.
- Condición futura, no bloqueante: el RUC real, la razón social, la dirección fiscal, el usuario SOL real y el certificado digital comprado se necesitan solo para el paso a producción, que exige autorización explícita del usuario. Ver `docs/integraciones/sunat.md`.

## Ola 2 — CERRADA (2026-08-19)

S-01-B y S-DO-01 completados y fusionados. Los dos fueron rechazados en su primera
validación y aprobados tras corregir; ningún defecto era estructural.

Lo que la ola deja, más allá de sus entregables: **cinco casos del mismo patrón de
configuración divergente** (ver arriba), y una garantía que resultó cierta en un
entorno y falsa en el otro — la revocación de `update`/`delete` sobre `auditorias`
funcionaba contra la instalación local y no existía dentro del contenedor, porque la
aplicación se conectaba como superusuario. QA verificó al cerrar que ahora los dos
entornos coinciden **tanto en lo que protegen como en lo que dejan abierto**: SEG-02
es el mismo hueco en ambos, con la misma causa.

Regla que se deriva, aplicable a S-DO-02 y a cualquier ambiente futuro: **una
garantía verificada en un entorno no está verificada en el otro.** Cada ambiente
nuevo revalida las garantías que dice sostener, no las hereda.

Dos cosas que QA declaró explícitamente como NO verificadas, y que no se dan por
buenas: el tiempo de detección de ~30 s del healthcheck (coherente con la
configuración leída, pero no medido de forma independiente), y el comportamiento de
`/up` al retroceder a un commit intermedio, donde responde 500 en HTML y 200 en JSON.
Esto último no se reprodujo en el código entregado y no pide acción; queda anotado
porque revela que el healthcheck depende del render HTML de esa ruta, y esa ruta
puede responder distinto según el `Accept`. Sirve si alguna vez aparece un contenedor
`unhealthy` con la aplicación aparentemente sana.

## Ola 3 — CERRADA (2026-08-19)

S-02-B, S-01-F y S-03-B completados y fusionados. S-01-F fue rechazado una vez; los
otros dos se aprobaron a la primera.

Lo que deja la ola, más allá de sus entregables:

- **La regla transversal de permisos en componentes**, encontrada en tres pisos
  sucesivos y con la forma del error nombrada. Es la regla más importante que produjo el
  proyecto hasta ahora.
- **El patrón de las dos fuentes sincronizadas**, con cuatro instancias.
- **Cuatro reglas nuevas para la práctica de mutación**, todas nacidas de errores reales
  cometidos al aplicarla.
- **Tres documentos aprobados corregidos porque afirmaban cosas que no se sostenían**: el
  motivo del índice parcial, el código de error del campo `codigo`, y el tamaño del
  catálogo 03 de SUNAT.

## Cómo se comprueba que algo está publicado — y por qué el comando obvio miente

> **Ampliación del 2026-08-20 — el nombre de la rama no es la unidad de verificación; el SHA
> sí.** Apareció midiendo, no buscando: `fix/pruebas-de-menu-derivadas` figuraba en `origin`
> y **lo publicado no era lo aprobado**. `qa` validó `6ae351f`; en `origin` estaba `cf0523d`,
> la versión previa a un rebase. Cualquier comprobación por nombre —"¿existe la rama en
> origin?"— la daba por publicada.
>
> Es la forma del día: **la respuesta era cierta y contestaba otra pregunta.** Un nombre de
> rama sobrevive a un rebase, a un amend y a un force-push, y esa persistencia es justo lo que
> lo vuelve inútil para decidir si algo está publicado.
>
> **Al fusionar, la comprobación se hace con el SHA que consta en el veredicto**, no con el
> nombre de la rama: `git branch -r --contains <sha>`. Si no devuelve ningún ref del remoto,
> ese SHA no está publicado, sin importar qué diga la lista de ramas. Es de `qa`.
>
> Y sirve como recuento honesto de la exposición: de 78 ramas locales sin publicar, solo seis
> refs tenían contenido fuera de `main` — las demás eran punteros de gobernanza ya fusionados.
> **Contar ramas exagera el riesgo; contar archivos que no existen en ningún otro lado lo
> mide.** Ese día eran siete.


**Al cerrar una jornada, verificar que el remoto tenga lo que el local tiene.** No alcanza
con que los `push` hayan parecido entrar: fallaron dos veces por caída de red y se reportó
"publicado" sin comprobar. Durante un rato real, S-05-B entero y una decisión de
Arquitectura existieron solo en este disco. Lo midieron `qa` e `implementation-frontend`
por separado.

La frase con la que veníamos cerrando —"nada depende de que esta conversación
sobreviva"— era cierta a medias. `qa` la corrigió: **no depende de la conversación, pero sí
de la máquina.** La durabilidad tenía un segundo eslabón que nadie comprobaba.

**Pero el comando obvio no sirve solo, y esto es lo importante.**
`git log origin/develop..develop` compara contra la **referencia local** de
`origin/develop`, que únicamente se actualiza con un `fetch` o un `push` exitosos. Si el
`fetch` falla —que es exactamente el escenario de red caída que motiva la comprobación— la
referencia queda vieja y el comando responde "cero pendientes" **mirando una foto de hace
horas**.

O sea: el comando de verificación puede pasar sin verificar nada. Es la misma forma de
fallo que este proyecto viene persiguiendo, un nivel más arriba — ya no es el mecanismo el
que falla en silencio, es la comprobación del mecanismo.

**El procedimiento, entonces:**

1. `git fetch` primero, y **comprobar que el fetch funcionó**.
2. Si el fetch falla, el estado de publicación es **desconocido**, no verde.
3. Recién con el fetch en verde, `git log origin/develop..develop` significa algo.
4. Para lo crítico, mirar el **árbol del remoto** y no solo el commit: un commit puede
   figurar y el árbol no tener lo que uno cree.

Lo señaló `qa`, sobre el procedimiento que se estaba incorporando para corregir el problema
anterior.

**Las ramas de sprint sin publicar son otra cosa, y pesan menos de lo que parecía.** El
remoto tiene cuatro ramas y el local doce, pero ninguna de las nueve de sprint guarda un
commit fuera de `develop`: todo el contenido está publicado.

Y la trazabilidad tampoco se pierde. `implementation-frontend` fue a comprobar si era
cierto que "se perdería la etiqueta de qué commit fue el `final_sha` de cada sprint" y
**no lo es**: este mismo documento registra por sprint el `branch`, el `base_sha`, el
`final_sha` y el `merge_sha`, y está versionado y publicado. Quien quisiera reconstruirlo
lo lee acá, y los commits siguen existiendo dentro de `develop`.

Lo que se perdería es la comodidad de una rama con nombre apuntando ahí. Eso baja el asunto
de **pérdida de trazabilidad** a **pérdida de conveniencia**.

Vale registrar por qué: la disciplina de anotar los SHA en el estado global se adoptó para
que un sprint se pudiera auditar sin depender de la conversación. Terminó siendo el
respaldo de la trazabilidad ante la pérdida de los punteros, sin que nadie lo diseñara para
eso.

## Alcance y proyección son dos preguntas distintas

**A quién pertenece un recurso** y **qué campos viajan dentro de él** se prueban por
separado, y cubrir la primera no cubre la segunda.

`implementation-backend` lo formuló al reconocer el costo visible al vendedor: *"miré el
acote de a quién pertenece la venta, no qué campos viaja adentro"*. Sus dos pruebas de
vendedor comprobaban el alcance —no ve ventas ajenas, sí ve las propias— y ninguna miraba
la proyección. QA lo confirmó de forma independiente.

Es fácil de cometer porque la primera pregunta se siente como la difícil: el acote por
propiedad es la regla explícita del contrato, la que uno va a buscar. La proyección suele
estar en una cláusula aparte, o —como acá— no estar.

**Al validar o al escribir pruebas de un recurso acotado, son dos aserciones distintas**, y
la de proyección es la que se olvida.

## La unicidad protege el dato; el bloqueo protege la operación

Son garantías distintas y **una prueba que solo mire integridad da por cubierto un
mecanismo ausente.**

Al automatizar la prueba de concurrencia en S-05-B, `implementation-backend` quitó el
bloqueo de la fila de la serie esperando ver correlativos duplicados. No aparecieron: la
restricción de unicidad de la base lo impide. Lo que apareció fue otra cosa — **tres de
cada cuatro ventas simultáneas fallan** con violación de unicidad. El dato queda íntegro y
tres clientes se quedan sin comprobante **después de que se les cobró**.

O sea: la unicidad garantiza que no haya dos correlativos iguales; el bloqueo garantiza que
la operación pueda completarse. Verificar solo lo primero deja pasar la ausencia de lo
segundo, y el síntoma no se parece en nada a un problema de concurrencia — se parece a
ventas que fallan.

La prueba comprueba las tres cosas por separado: integridad del dato, que la operación no
muera, y que el bloqueo esté.

**El daño es peor de lo que la primera descripción sugería.** Se escribió como "tres
clientes sin comprobante después de que se les cobró", y `qa` precisó que el rollback
revierte también el descuento de stock: la venta no queda a medias en los datos. Eso suena
a mitigación y no lo es — significa que **no queda ningún rastro de esas ventas**. El
cajero cobró, el sistema dice que no pasó nada, y no hay nada que reconciliar después: hay
que rehacerlas de memoria. La integridad intacta es justamente lo que borra la evidencia.

## Dos formas en que una prueba de concurrencia pasa sin probar nada

Las dos las encontró `implementation-backend` al escribir la de S-05-B, y las dos daban
verde:

1. **Con un solo par de procesos.** La ventana entre leer y escribir dura microsegundos, y
   dos procesos sincronizados rara vez la comparten. La prueba pasaba **sin el bloqueo**.
   Van tres pares.
2. **Con los procesos hijos muertos al arrancar.** No competía nadie, así que no había
   divergencia que detectar. Ahora se verifica que corrieron y dejaron rastro, en vez de
   suponerlo.

La segunda es la misma familia que "confirmá que la mutación se aplicó" y que "un cero de
un barrido no es evidencia de ausencia": **el procedimiento no se ejecutó, y su no-ejecución
se lee igual que un resultado limpio.**

## Por qué existen las reglas de verificación — un hallazgo grande se siente como un buen resultado

Las cuatro reglas de la práctica de mutación nacieron de casos donde **el resultado
engañoso era más cómodo que el correcto**. Vale tener escrito el mecanismo, porque es lo
que se repite:

`implementation-backend` obtuvo "DIVERGEN" en su primera prueba de concurrencia de S-04-B,
con el bloqueo de fila puesto. La lectura inmediata era *encontré un defecto grave en el
mecanismo más importante del sprint* — una lectura atractiva, que confirmaba que la
mutación servía y que valía la pena mirar. Investigar antes de reportar significaba
arriesgarse a que el hallazgo se desinflara. Se desinfló: el defecto estaba en su
escenario, que creaba el lote con una factory que no escribe el movimiento de ingreso.

Su formulación, que es la que importa: **la tentación no es reportar rápido por descuido,
es que un hallazgo grande se siente como un buen resultado.** Por eso las reglas no piden
más atención, piden un paso concreto antes de concluir.

## Un mensaje de error describe el mecanismo que falló, no la causa

Dos casos opuestos, misma suposición rota:

- **`lsof`** devolvió salida vacía sin error cuando no podía ver los sockets ajenos. La
  herramienta **calló** lo que no podía ver.
- **PostgreSQL** dice "permiso denegado sobre `movimientos_inventario`" al intentar borrar
  un lote, porque necesita bloquear la fila hija para verificar la clave foránea y ese
  bloqueo exige privilegio de escritura. El motor **dice la verdad** sobre el mecanismo que
  falló, y esa verdad apunta lejos de la causa: quien lo lea sale a revisar `GRANT`s
  cuando lo que ocurre es una clave foránea haciendo su trabajo.

Los dos rompen la suposición de que el mensaje describe el problema, y los dos se
resuelven igual: **comprobando, no leyendo**. Consecuencia directa para S-DO-02, señalada
por `devops`: un error de permisos en producción no se diagnostica por su texto.

## Regla de barrido — un cero no es evidencia de ausencia

**Un barrido encuentra lo que su patrón sabe mirar, y devuelve cero sin distinguirlo de
"no existe".** Antes de concluir que algo falta, hay que saber que el barrido miró donde
debía.

Tres veces en dos días, y las tres por causas distintas:

- El guardián de arquitectura de S-00 buscaba sufijos en inglés y no veía `VentaServicio`
  ni `ProductoRepositorio`. Devolvía lista vacía.
- Un inventario de directorios de prueba filtró por archivos `.php` y contó cuatro
  directorios sin dueño cuando eran cinco: `tests/recursos/` tiene un `.blade.php`.
- Coordinación buscó un componente en `app/Dominios/Interfaz/Livewire/` cuando vive en
  `Usuarios/Livewire/`, obtuvo cero coincidencias y estuvo a punto de reportar que no
  estaba anotado.

La formulación es de `implementation-frontend`, que cometió la segunda y nombró la
tercera. Es hermana de la regla de mutación "confirmá que la mutación se aplicó": las dos
distinguen *no encontré nada* de *no hay nada*.

## Una prueba sin aserciones pasa siempre

`implementation-frontend` vació la lista de pendientes de su propia prueba guardiana y
PHPUnit la marcó como **arriesgada**: con la lista vacía, el bucle no ejecutaba ninguna
aserción. **Su prueba contra las listas que envejecen se estaba convirtiendo en lo que
vigila** — la que hoy no tiene nada que mirar es indistinguible de la que dejó de mirar.

Corregida afirmando sobre el conjunto y no dentro del bucle, así hace siempre una
aserción, con lista vacía o no. Verificada por mutación en las dos direcciones.

Vale registrar quién lo detectó: **la herramienta, no el criterio de quien la escribió**.
Es el argumento que este proyecto viene acumulando a favor del mecanismo sobre la
disciplina, esta vez a favor de una herramienta que nadie eligió por ese motivo.

## Siguiente fase — ola 4

**S-04-B** (compras, lotes, kardex y ajustes) y **S-02-F** (pantallas de catálogo,
proveedores, clientes y usuarios). Dependencias satisfechas: los dos dependen de S-02-B
y S-03-B, y S-02-F además de S-01-F.

**HABILITADA el 2026-08-20.** La dependencia cruzada se resolvió: el mecanismo está
implementado, aprobado y fusionado en `develop@e87aded`, junto con el retiro de endpoints
de ADR-0006 y las doce pruebas reparadas.

| Carril | Sprint | Rol | Rama | Base de carril |
|---|---|---|---|---|
| 1 | **S-04-B** — compras, lotes, kardex y ajustes | `implementation-backend` | `sprint/S-04-B` | `ventas_inventario_s04b_test` |
| 2 | **S-02-F** — pantallas de catálogo, proveedores, clientes y usuarios | `implementation-frontend` | `sprint/S-02-F` | `ventas_inventario_s02f_test` |

Ambos parten de `develop@e87aded`, cada uno con su worktree fuera del árbol compartido.

### Inventario de estado externo — hecho ejerciendo, no consultando

| Recurso | Estado real | Decisión |
|---|---|---|
| PostgreSQL | **Conecta** con el rol de la aplicación. Comprobado conectándose, no mirando el puerto — `lsof` ya mintió una vez | Base por carril, creada por cada uno |
| Puertos 8000, 8080, 5173 | **Sin respuesta**, comprobado con petición real y no con listado | Libres. Si los dos carriles quieren el 8000, coordina el Coordinador |
| Contenedores | Solo `reservas-canchas-mysql` en 3307, de otro proyecto | No interfiere |
| Bases acumuladas | Siete: la de aplicación, la genérica y cinco de carril de sprints cerrados | No se borran; su nombre dice a qué sprint pertenecen y `RefreshDatabase` las recompone |

### Dependencia que se resolvió y por qué se registra

**Historial anterior de esta sección:** La
enmienda de S-02-F exige el mecanismo que hace fallar a un componente que escribe sin
declarar permiso, y ese mecanismo vive en `app/Compartido/`, que es ruta de
`implementation-backend`. Si S-02-F lo necesitara mientras S-04-B corre, dos sesiones
escribirían el mismo árbol.

Secuencia decidida:

1. Los dos frentes proponen juntos la forma del mecanismo. Arquitectura aprueba.
2. `implementation-backend` lo implementa como tarea corta, antes de abrir S-04-B.
3. Recién entonces S-04-B y S-02-F corren en paralelo, cada uno en su worktree y con su
   base de carril.

Frontend puede avanzar mientras tanto lo que no dependa del mecanismo.

## Ola 3 — despacho original

**S-02-B, S-03-B y S-01-F**, declarados paralelizables entre sí en el roadmap.

Antes de habilitarla hay que resolver una limitación que no es técnica: los dos
sprints `-B` corresponden al mismo rol y hoy existe **un solo chat de Backend**. El
roadmap declara que pueden correr en paralelo, pero el paralelismo entre sprints
exige una sesión por carril; un chat no atiende dos sprints a la vez. Las opciones
son abrir un segundo chat de Backend o ejecutar S-02-B y S-03-B en secuencia dentro
del mismo. Es decisión del usuario y está pendiente.

**Dato que pesa sobre esa decisión, aportado por `implementation-backend`:** S-02-B y
S-03-B comparten `database/migrations/` y `config/`. O sea que dos chats de backend en
paralelo tendrían **contención real de archivos**, no solo de puertos y bases. El
roadmap los declara paralelizables, y esa declaración sigue siendo válida a nivel de
dependencias funcionales —ninguno necesita el resultado del otro—, pero materializarla
en dos sesiones simultáneas chocaría en el árbol. Es exactamente el caso que la Fase 3
de la skill describe: buckets que se creían disjuntos y no lo son. Ejecutarlos en
secuencia no contradice el roadmap: renuncia al paralelo por una razón operativa real,
que es una salida explícitamente válida.

Aislamiento ya resuelto para cuando se habilite: un worktree por carril fuera del
árbol compartido, y una base por carril según la convención de arriba
(`ventas_inventario_<carril>_test`), que el `CREATEDB` otorgado el 2026-08-19 hace
posible. Falta inventariar los puertos **mirando la máquina**, no razonando sobre
ella, con el entorno contenerizado ya en juego.

## Ola 2 — inventario que se usó

**S-01-B** (`implementation-backend`) y **S-DO-01** (`devops`) en paralelo, ambos
`LISTO`, ambos partiendo de `develop@<sha de cierre de S-00>`. Es el primer
paralelismo real del proyecto y el primer turno del chat de DevOps.

Inventario de estado externo compartido, hecho antes de despachar (el worktree
aísla archivos y ramas, no lo de afuera):

| Recurso | ¿Colisiona? | Decisión |
|---|---|---|
| Árbol de trabajo | Sí | **Separar**: un `git worktree` por carril, ninguno en `/Users/sankef/ventas-inventario` |
| Base `ventas_inventario_test` | No | Solo S-01-B la usa. S-DO-01 levanta su propio PostgreSQL en contenedor |
| Puerto 5432 | **Sí** | Lo ocupa el PostgreSQL del host. S-DO-01 **no publica** el suyo: los servicios del compose se hablan por su red interna. Además de evitar la colisión, vuelve imposible que el contenedor escriba por error en las bases del host |
| Puerto 8000 | **Sí** | Es el que el README documenta para `php artisan serve`, así que lo va a usar Backend al verificar S-01-B a mano. S-DO-01 publica su aplicación en **8080** y deja 8000 libre |
| Puerto 5173 | No | Libre, verificado |
| `vendor/`, `node_modules/` | No | Cada worktree instala lo suyo |

El conflicto del puerto 8000 lo detectó DevOps al verificar la máquina: mi inventario
inicial daba el 5432 como el único de la ola y era incorrecto. Un inventario de
estado externo no se completa razonando sobre la topología — se completa mirando qué
está ocupado.

No hace falta serializar en esta ola: solo un carril toca la base local. En la
**ola 3** eso deja de ser cierto — tres carriles simultáneos contra una sola base
de pruebas — y ahí sí hace falta el arreglo estructural, ver "Pendientes de
planificación".

Frontend sigue esperando: S-01-F depende de S-01-B, no de S-00. Los demás sprints
quedan en `PLANIFICADO` hasta que sus dependencias se completen.

## Referencias
- Roadmap: este documento, sección "Roadmap del horizonte"
- Prompts de apertura de los chats de rol: docs/chats-de-rol.md
- Contrato entre backend y frontend: docs/contratos/servicios-de-dominio.md
- Handoffs de sprint: docs/handoffs/
- Handoff activo: docs/handoffs/S-00.md, en la rama `sprint/S-00`
- Decisiones y contratos: docs/decisiones/, docs/contratos/, docs/persistencia/modelo.md

---

# Roadmap del horizonte

Cada sprint declara qué produce de forma observable, de qué documentos
aprobados nace, de qué depende y con qué puede correr en paralelo. El sufijo
`-B` marca sprints de backend, `-F` de frontend, `-DO` de DevOps y `-QA` de
validación.

El rol `implementation` está declarado en `AGENTS.md` en dos variantes con rutas
disjuntas, para que los dos frentes puedan trabajar a la vez sin escribir los
mismos archivos: `implementation-backend` (sufijo `-B`) e `implementation-frontend`
(sufijo `-F`). `S-00` es la excepción: funda el proyecto usando ambas rutas, así
que se ejecuta solo.

| ID | Rol | Resultado observable | Fuentes | Depende de | Paralelizable con | RFC |
|---|---|---|---|---|---|---|
| S-00 | implementation-backend | Proyecto Laravel fundado y corriendo en local, con PostgreSQL, Tailwind vía Vite, la estructura `app/Dominios/`, y los comandos de lint, pruebas y build funcionando de verdad | ADR-0001, ADR-0005, AGENTS.md | — | — | docs/rfcs/S-00.md |
| S-DO-01 | devops | Entorno reproducible con Docker Compose: aplicación, PostgreSQL y proceso trabajador de cola, levantables con un comando | ADR-0001, ADR-0003, RNF-002 | S-00 | S-01-B | docs/rfcs/S-DO-01.md |
| S-01-B | implementation-backend | Inicio y cierre de sesión, alta y edición de usuarios, y control de acceso por rol aplicado en el servidor sobre cada ruta | RF-001, RF-002, MIG-001, actores-permisos, contratos/usuarios | S-00 | S-DO-01 | docs/rfcs/S-01-B.md |
| S-02-B | implementation-backend | Categorías y productos con precio menor y mayor, stock mínimo y su validación de precios | RF-003, RF-004, MIG-002, contratos/productos | S-01-B | S-03-B, S-01-F | docs/rfcs/S-02-B.md |
| S-03-B | implementation-backend | Proveedores y clientes con validación de documento según su tipo | RF-005, RF-010, MIG-003, MIG-006, contratos/proveedores, contratos/clientes | S-01-B | S-02-B, S-01-F | docs/rfcs/S-03-B.md |
| S-04-B | implementation-backend | Compras que crean lotes con vencimiento y costo, consulta de stock por lote, kardex inmutable y ajustes con motivo | RF-006, RF-007, RF-008, RF-009, MIG-004, MIG-005, ADR-0004, contratos/compras, contratos/inventario | S-02-B, S-03-B | S-02-F | docs/rfcs/S-04-B.md |
| S-05-B | implementation-backend | Venta registrada en una transacción: descuento FEFO con reparto por lote, cálculo de IGV, reserva de correlativo y comprobante en estado pendiente | RF-011, RF-012, RF-013, RF-014, MIG-007, MIG-008, RNF-003, contratos/ventas | S-04-B | S-03-F | docs/rfcs/S-05-B.md |
| S-06-B | implementation-backend | Emisión electrónica real contra el ambiente beta de SUNAT: XML firmado, envío en segundo plano con reintentos, constancia CDR guardada, reenvío manual y resumen diario de boletas | RF-015, RF-016, RF-017, ADR-0002, ADR-0003, integraciones/sunat, contratos/comprobantes | S-05-B | S-07-B, S-08-B, S-04-F | docs/rfcs/S-06-B.md |
| S-07-B | implementation-backend | Consultas de alertas de vencimiento y stock bajo, y reportes de ventas y de utilidad con costo real por lote | RF-018, RF-019, RF-020, RF-021, contratos/inventario, contratos/ventas | S-05-B | S-06-B, S-08-B, S-04-F | docs/rfcs/S-07-B.md |
| S-08-B | implementation-backend | Política completa de auditoría aplicada a cada operación sensible, y registro de errores con saneamiento de secretos y canal independiente | RNF-004, RNF-014, modelo (Auditoria, LogError) | S-04-B | S-06-B, S-07-B | docs/rfcs/S-08-B.md |
| S-01-F | implementation-frontend | Base de la interfaz: layout, menú por rol, componentes reutilizables y pantalla de inicio de sesión | RF-001, RF-002, frontend/experiencia, frontend/integracion | S-01-B | S-02-B, S-03-B | docs/rfcs/S-01-F.md |
| S-02-F | implementation-frontend | Pantallas de catálogo, proveedores, clientes y usuarios, con sus validaciones en el momento de escribir | RF-002, RF-003, RF-004, RF-005, RF-010, frontend/experiencia | S-02-B, S-03-B, S-01-F | S-04-B | docs/rfcs/S-02-F.md |
| S-03-F | implementation-frontend | Pantallas de compra, consulta de inventario por lote, kardex y ajuste | RF-006, RF-007, RF-008, RF-009, frontend/experiencia | S-04-B, S-01-F | S-05-B | docs/rfcs/S-03-F.md |
| S-04-F | implementation-frontend | Pantalla de caja completa: búsqueda por teclado y código de barras, precio menor o mayor por línea, confirmación e impresión del comprobante | RF-011, RF-012, RF-013, RNF-008, frontend/experiencia | S-05-B, S-01-F | S-06-B, S-07-B | docs/rfcs/S-04-F.md |
| S-05-F | implementation-frontend | Pantalla de seguimiento de comprobantes con reenvío, y pantalla de resúmenes diarios | RF-016, RF-017, frontend/experiencia | S-06-B, S-01-F | S-06-F | docs/rfcs/S-05-F.md |
| S-06-F | implementation-frontend | Tablero de alertas de vencimiento y stock bajo, y pantallas de reportes de ventas y utilidad | RF-018, RF-019, RF-020, RF-021, frontend/experiencia | S-07-B, S-01-F | S-05-F | docs/rfcs/S-06-F.md |
| S-09-B | implementation-backend | Pruebas de extremo a extremo del recorrido de venta, y prueba de rendimiento que mide el umbral de confirmación de venta sobre volumen realista | RNF-001, RNF-008, AGENTS.md (comando E2E) | S-04-F | S-05-F, S-06-F | docs/rfcs/S-09-B.md |
| S-QA-01 | qa | Veredicto de validación integral: funcional sobre todo el horizonte, concurrencia, seguridad de aplicación y emisión contra el ambiente beta de SUNAT | RNF-001 a RNF-014, todos los RF | S-09-B, S-05-F, S-06-F | — | docs/rfcs/S-QA-01.md |
| S-DO-02 | devops | Despliegue del sistema en el servidor, con respaldo diario probado, verificación de salud, recolección de logs y procedimiento de reversión | RNF-002, RNF-007, AGENTS.md (operación DevOps) | S-QA-01, S-DO-01 | — | docs/rfcs/S-DO-02.md |

## Matriz de cobertura

| Fuente | Sprint que la cubre |
|---|---|
| RF-001, RF-002 | S-01-B (servidor), S-01-F (pantalla) |
| RF-003, RF-004 | S-02-B, S-02-F |
| RF-005, RF-010 | S-03-B, S-02-F |
| RF-006, RF-007, RF-008, RF-009 | S-04-B, S-03-F |
| RF-011, RF-012, RF-013, RF-014 | S-05-B, S-04-F |
| RF-015, RF-016, RF-017 | S-06-B, S-05-F |
| RF-018, RF-019, RF-020, RF-021 | S-07-B, S-06-F |
| RNF-001 (rendimiento) | S-09-B mide, S-QA-01 valida |
| RNF-002 (disponibilidad ante SUNAT) | S-06-B implementa, S-QA-01 valida |
| RNF-003 (integridad y concurrencia) | S-04-B y S-05-B implementan, S-QA-01 valida |
| RNF-004 (trazabilidad) | S-01-B crea las tablas, cada sprint audita lo suyo, S-08-B cierra la política completa |
| RNF-005 (custodia del certificado) | S-06-B implementa la alerta, S-DO-02 la custodia en el servidor |
| RNF-006 (fecha, hora y moneda) | S-00 configura, cada sprint de dominio lo respeta, S-QA-01 valida |
| RNF-007 (respaldo) | S-DO-02 |
| RNF-008 (operación con teclado) | S-04-F implementa, S-09-B automatiza, S-QA-01 valida |
| RNF-010 a RNF-014 (seguridad) | cada sprint las aplica; S-QA-01 las valida con `seguridad-validacion` |
| MIG-001 a MIG-009 | S-01-B (MIG-001 y MIG-009), S-02-B (MIG-002), S-03-B (MIG-003, MIG-006), S-04-B (MIG-004, MIG-005), S-05-B (MIG-007, MIG-008) |
| Integración SUNAT | S-06-B |
| AGENTS.md — lint, pruebas, build, gestor de paquetes (`previsto en S-00`) | S-00 |
| AGENTS.md — comando E2E (hoy `no aplica`) | S-09-B lo define y lo vuelve real |
| AGENTS.md — pipeline, artefacto, health/smoke, observabilidad, reversión (`previsto al definir el despliegue`) | S-DO-02 |
| AGENTS.md — navegadores y viewports de QA | S-01-F los fija junto con la base de interfaz; S-QA-01 los usa |

## Olas de ejecución

1. **Ola 1** — S-00. Nada más puede empezar antes.
2. **Ola 2** — S-01-B y S-DO-01 en paralelo.
3. **Ola 3** — S-02-B, S-03-B y S-01-F en paralelo.
4. **Ola 4** — S-04-B y S-02-F en paralelo.
5. **Ola 5** — S-05-B y S-03-F en paralelo.
6. **Ola 6** — S-06-B, S-07-B, S-08-B y S-04-F en paralelo.
7. **Ola 7** — S-05-F, S-06-F y S-09-B en paralelo.
8. **Ola 8** — S-QA-01.
9. **Ola 9** — S-DO-02.

---

# Avisos al usuario — obligación del Coordinador

Hay puntos del proyecto que **no se pueden resolver sin el dueño del negocio**.
El Coordinador es responsable de avisarle **antes** de que el sprint se bloquee,
no cuando ya está detenido. Si un sprint llega a su turno y falta uno de estos
datos, se marca `BLOQUEADO` y el motivo se reporta explícitamente al usuario.

Ningún rol asume, inventa ni deja "para después" uno de estos puntos, y ninguno
se salta el aviso porque "seguro lo tiene". El aviso se da aunque el Coordinador
crea que el usuario ya lo sabe.

| Cuándo avisar | Qué se necesita del usuario | Qué se bloquea si falta |
|---|---|---|
| Al cerrar **S-05-B**, antes de habilitar S-06-B | RUC y razón social del emisor, dirección fiscal, usuario secundario SOL y su clave, y el **certificado digital de pruebas**. Todo esto es para el ambiente **beta**: son datos de prueba, no el certificado real de producción | S-06-B completo, y con él S-05-F. El resto del proyecto sigue avanzando |
| Al cerrar **S-QA-01**, antes de habilitar S-DO-02 | Decisión del proveedor y servidor donde vivirá el sistema, con PostgreSQL disponible. Si el proveedor elegido no lo ofrece, hay que reabrir el ADR-0001 antes de continuar | S-DO-02 completo |
| Dentro de **S-DO-02**, antes de cada operación | Autorización explícita para desplegar, para instalar el **certificado digital real**, y para cambiar el ambiente de SUNAT de `beta` a `produccion`. Se pide inmediatamente antes de cada acción, nunca por adelantado ni en bloque | La operación puntual, no el sprint |
| Antes de cualquier `git push` que no esté pre-autorizado | Confirmación puntual en el chat | El push |
| Cuando QA emite **RECHAZADO** o **BLOQUEADO** | Que el usuario sepa qué falló y decida si se corrige, se posterga o se acepta el riesgo | El cierre del sprint |
| Cuando un cambio invalida un documento aprobado | Reaprobación del documento afectado (RFC, contrato, `AGENTS.md`) | Los sprints que dependan de ese documento |
| Cuando aparezca una decisión que cambie alcance, costo o riesgo | Su decisión, con las alternativas explicadas antes de preguntar | Lo que dependa de esa decisión |

**Sobre el aviso de SUNAT en particular**, que es el más previsible: el Coordinador
lo anuncia al cerrar S-05-B, no al empezar S-06-B. Conseguir un certificado de
pruebas y un usuario SOL toma tiempo, y avisar el mismo día en que hace falta
significa detener el proyecto por trámite.
