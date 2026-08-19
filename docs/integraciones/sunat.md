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
| Tope de boleta sin identificar al cliente | S/ 700 | **propuesto** — pendiente de confirmación del usuario |
| RUC del emisor, razón social, dirección fiscal | del negocio real | pendiente — se completa antes del sprint de emisión |
| Usuario secundario SOL del emisor | requerido para el envío | pendiente — se completa antes del sprint de emisión |

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

Aprobado por: Kevin Espíritu (kevinespiritu16@gmail.com) — fecha: 2026-08-19
Parte técnica aprobada por (Arquitectura): sesión de Arquitectura del 2026-08-19 — fecha: 2026-08-19
