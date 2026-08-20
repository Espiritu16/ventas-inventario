<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tope de boleta sin identificar al cliente
    |--------------------------------------------------------------------------
    |
    | Importe a partir del cual una boleta exige documento del cliente (RF-010).
    | S/ 700, aprobado por el usuario el 2026-08-19.
    |
    | Es configurable por variable de entorno a propósito: si la norma cambia el
    | monto, ajustarlo no debería exigir tocar código ni volver a desplegar una
    | versión nueva por un número.
    |
    */

    'tope_boleta_sin_documento' => env('VENTA_TOPE_BOLETA_SIN_DOCUMENTO', '700.00'),

];
