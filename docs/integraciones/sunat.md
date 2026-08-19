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
| Catálogos que el sistema usa | tipo de documento de identidad, tipo de comprobante, unidad de medida, tipo de afectación de IGV | fijado |
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

Aprobado por: pendiente — revisión reabierta el 2026-08-19 al separar lo que hace falta en beta de lo que hace falta en producción
Parte técnica aprobada por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
