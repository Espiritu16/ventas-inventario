# Integración — SUNAT (emisión electrónica propia)

## SUNAT — servicio de comprobantes electrónicos
- Qué resuelve: el negocio emite boletas y facturas electrónicas válidas ante la administración tributaria peruana. El sistema construye el comprobante, lo firma con el certificado digital del emisor y lo envía directamente a SUNAT, sin proveedor intermediario (ver [ADR-0002](../decisiones/0002-emision-electronica-propia.md)).
- Dirección: el proyecto consume el servicio de SUNAT. SUNAT no consume nada del proyecto.
- Contrato del tercero: documentación oficial de SUNAT sobre comprobantes electrónicos y formato UBL 2.1, más la documentación de la librería Greenter usada para construir y firmar los documentos. No se copia acá; se enlaza desde el README del proyecto al fundarlo.

## Qué debe estar fijado antes de implementar

| Dato | Valor | Estado |
|---|---|---|
| Ambiente de desarrollo y validación | **beta** (ambiente de pruebas de SUNAT). Único autorizado sin aprobación explícita del usuario, según `AGENTS.md` | fijado |
| Ambiente de producción | prohibido sin autorización explícita inmediatamente antes de cada uso | fijado |
| Documentos que se emiten en esta versión | factura (`01`), boleta (`03`) y resumen diario de boletas | fijado |
| Documentos fuera de alcance | nota de crédito, nota de débito, comunicación de baja, guía de remisión | fijado |
| Formato | XML UBL 2.1 firmado digitalmente | fijado |
| Respuesta que se conserva | constancia CDR devuelta por SUNAT, almacenada junto al comprobante | fijado |
| Mecanismo del resumen diario | envío que devuelve un identificador de consulta, con el resultado obtenido en una consulta posterior | fijado |
| Catálogos que el sistema usa | tipo de documento de identidad, tipo de comprobante, unidad de medida, tipo de afectación de IGV | fijado — ver "Catálogo 03" abajo |
| Tasa de IGV | 18 %, incluida en los precios y desglosada al emitir | fijado |
| Tope de boleta sin identificar al cliente | S/ 700 | fijado — aprobado por el usuario el 2026-08-19 |
| Identidad del emisor en **beta** | credenciales de prueba de uso general publicadas por SUNAT. Se confirman contra la documentación vigente al implementar S-06-B, nunca de memoria | fijado — no requiere ningún dato del negocio |
| Certificado para firmar en **beta** | certificado de prueba (el que distribuye Greenter para ese fin) | fijado — no requiere comprar nada |
| RUC del emisor, razón social, dirección fiscal **reales** | del negocio | pendiente — **solo para producción**; no bloquea el desarrollo ni la validación |
| Usuario secundario SOL **real** | lo crea el dueño del negocio en el portal de SUNAT | pendiente — **solo para producción** |
| Certificado digital tributario **real** | comprado a nombre del RUC, con renovación anual | pendiente — **solo para producción** |

**Qué se necesita y cuándo.** Todo el desarrollo de S-06-B y toda la validación
de S-QA-01 ocurren en beta, con credenciales y certificado de prueba: no hacen
falta datos del negocio ni ningún gasto. Los datos reales y el certificado
comprado se necesitan únicamente para el paso a producción, que además exige
autorización explícita del usuario según `AGENTS.md`. Confundir ambas cosas
haría creer que el sprint de emisión está bloqueado cuando no lo está.

## Quién pide estos datos, y cuándo

Los tres pendientes de la tabla anterior —RUC y razón social del emisor, usuario
secundario SOL, y el certificado digital— **son para el ambiente beta**: sirven
para probar los envíos, no para emitir comprobantes reales.

El **Coordinador se los pide al usuario al cerrar S-05-B**, es decir un sprint
antes de que hagan falta, porque obtenerlos toma tiempo. No se piden el día en que
S-06-B arranca. Ver la sección "Avisos al usuario" de `docs/estado-global.md`.

El certificado **real** de producción es otra cosa y se pide por separado, dentro
de S-DO-02 y con autorización explícita inmediatamente antes de instalarlo.

## Dónde vive la credencial

Nunca en el repositorio, nunca en documentación, nunca en logs (RNF-005, RNF-014).

| Concepto | Variable en `.env.example` |
|---|---|
| Ruta del archivo del certificado digital | `SUNAT_CERTIFICADO_RUTA` |
| Clave del certificado | `SUNAT_CERTIFICADO_CLAVE` |
| Usuario secundario SOL | `SUNAT_USUARIO_SOL` |
| Clave del usuario SOL | `SUNAT_CLAVE_SOL` |
| RUC del emisor | `SUNAT_RUC` |
| Ambiente (`beta` o `produccion`) | `SUNAT_AMBIENTE` |

El archivo del certificado se guarda fuera del árbol del repositorio y su ruta
se configura por variable de entorno. `.gitignore` excluye cualquier archivo de
certificado por extensión, como red de seguridad adicional.

## Manejo de fallas

| Situación | Comportamiento del sistema |
|---|---|
| SUNAT no responde, responde con error de servicio o agota el tiempo de espera | `SUNAT_NO_DISPONIBLE`. El comprobante permanece `PENDIENTE` y se reintenta con esperas crecientes. La venta ya está registrada y no se ve afectada (RNF-002) |
| SUNAT rechaza el comprobante por sus datos | `SUNAT_RECHAZO`. El comprobante pasa a `RECHAZADO` con el código y mensaje exactos. **No se reintenta** |
| SUNAT acepta con observaciones | Se trata como aceptado y las observaciones se conservan en `mensaje_sunat` para que el administrador las vea |
| El certificado no se puede cargar | `CERTIFICADO_NO_DISPONIBLE`. Ningún comprobante se firma; se registra en `LogError` con severidad `critical` y se muestra en el panel |
| El certificado está vencido | `CERTIFICADO_VENCIDO`. El sistema avisa con 30 días de anticipación para que no se llegue a este punto (RNF-005) |
| Se agotan los reintentos | El comprobante queda `PENDIENTE` y visible en la pantalla de seguimiento, que es el mecanismo previsto para la intervención manual (RF-016) |

## Catálogo 03 — código de tipo de unidad de medida comercial

**El anexo oficial de SUNAT no enumera códigos. Delega en un estándar
internacional.** Verificado el 2026-08-19 leyendo el anexo oficial publicado por
SUNAT (`https://www2.sunat.gob.pe/facturador/AnexosIyII_Formato1.3.4.xlsx`, hoja
"Catálogos"): bajo el encabezado del catálogo 03, en lugar de una tabla de códigos,
el anexo dice literalmente **"UN/ECE Recommendation 20 Revision 13"** y enlaza a
`https://www.unece.org/fileadmin/DAM/uncefact/recommendations/rec20/rec20_Rev13e_2017.xls`.

Esto corrige una premisa equivocada con la que se estaba trabajando. `implementation-backend`
reportó honestamente que no había podido verificar "la lista de SUNAT" y pidió no
aprobar una lista de terceros como si estuviera verificada. Tenía razón en frenar, y
el motivo por el que no la encontraba es que **esa lista no existe**: quien la
publica es UN/ECE, no SUNAT, y tiene del orden de mil ochocientos códigos.

Decisiones que se derivan, tomadas por Arquitectura:

1. **La fuente canónica del catálogo 03 es UN/ECE Recommendation 20 Revision 13.**
   Cualquier verificación futura se hace contra ese documento, no contra guías de
   terceros ni contra una tabla de SUNAT que no existe.
2. **El sistema valida contra un subconjunto declarado, no contra los mil ochocientos
   códigos.** Una comercializadora no vende en unidades astronómicas ni en barriles
   de petróleo, y aceptar cualquier código volvería inútil la validación: su propósito
   es que un error de carga no llegue al comprobante.
3. **Ese subconjunto es una decisión del proyecto, no un hecho sobre SUNAT.** Se
   documenta como tal, con el motivo de qué se incluye. Ampliarlo cuando el negocio lo
   necesite es trivial y no rompe nada; el registro tiene que dejar claro que la
   restricción es nuestra.
4. **`NIU` (unidad) se mantiene como valor por defecto**, tal como ya declara
   `docs/contratos/productos.md`.

El subconjunto concreto lo propone `implementation-backend` en el handoff de S-02-B,
derivado de UN/ECE Rec 20 Rev 13, y lo aprueba Arquitectura antes del cierre del
sprint. La validación no bloquea el sprint: el mecanismo se implementa contra la lista
que esté en configuración, y la lista se sustituye al aprobarse.

Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — reaprobado el 2026-08-19 tras separar lo que hace falta en beta de lo que hace falta en producción
Parte técnica aprobada por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
