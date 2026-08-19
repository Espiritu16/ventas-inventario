# ADR-0002: Emisión electrónica propia ante SUNAT con Greenter

- Estado: aceptada
- Aprobado por (Arquitectura): sesión de Arquitectura del 2026-08-19, sobre la decisión de negocio tomada por Kevin Espíritu — fecha: 2026-08-19

## Contexto

El negocio entrega comprobantes electrónicos válidos ante SUNAT. Había dos
familias de solución: contratar un proveedor autorizado que firme y envíe por
el sistema, o emitir directamente contra SUNAT. El usuario eligió emitir
directamente, con la consecuencia expuesta y aceptada de asumir el certificado
digital y la conformidad normativa.

## Decisión

Emisión propia usando Greenter (PHP) para construir el XML UBL 2.1, firmarlo
con el certificado digital del emisor y comunicarse con SUNAT.

Greenter no se invoca desde el dominio de ventas. Se accede a través de una
interfaz propia, `EmisorElectronico`, con una implementación `EmisorGreenter`.
El dominio de ventas y el de comprobantes solo conocen la interfaz.

## Consecuencias

- No hay costo por comprobante, pero sí el costo anual del certificado digital tributario y el trabajo de mantener la conformidad ante cambios normativos de SUNAT.
- El sistema carga con el ciclo completo: envío, constancia CDR, resumen diario de boletas y control de rechazos.
- Cambiar a un proveedor autorizado más adelante significa escribir una implementación nueva de `EmisorElectronico`, sin tocar ventas ni la pantalla de caja.
- Todo el desarrollo y toda la validación de QA ocurren contra el ambiente **beta** de SUNAT. El ambiente de producción y el certificado real quedan prohibidos sin autorización explícita del usuario, según `AGENTS.md`.
