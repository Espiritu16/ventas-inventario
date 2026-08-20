<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unidades de medida — Catálogo 03
    |--------------------------------------------------------------------------
    |
    | Códigos que pueden viajar en el comprobante electrónico. SUNAT rechaza el
    | documento entero si recibe uno que no está en su catálogo, así que la
    | validación ocurre al crear el producto y no al emitir: descubrirlo recién
    | en la caja significaría una venta que no se puede facturar.
    |
    | PROPUESTA, pendiente de aprobación de Arquitectura. La lista tiene los 62
    | códigos que las guías de referencia reproducen del Catálogo 03, pero el
    | servidor de SUNAT no fue accesible al implementar este sprint, así que
    | NO está verificada contra la fuente oficial. Antes de emitir contra el
    | ambiente beta en S-06-B hay que confirmarla contra el anexo vigente.
    |
    */

    'unidades_de_medida' => [
        // Unidades y conteo
        'NIU', 'ZZ', 'C62', 'PR', 'SET', 'KT', 'DZN', 'GRO', 'CEN', 'MLL', 'UM', 'DZP',
        // Longitud
        'MTR', 'CMT', 'MMT', 'KTM', 'FOT', 'INH', 'YRD',
        // Superficie
        'MTK', 'CMK', 'MMK', 'FTK', 'YDK',
        // Volumen
        'MTQ', 'CMQ', 'MMQ', 'FTQ', 'LTR', 'MLT', 'HLT', 'GLL', 'GLI',
        // Peso
        'KGM', 'GRM', 'MGM', 'TNE', 'STN', 'LTN', 'LBR', 'ONZ',
        // Empaques y presentaciones
        'BX', 'BG', 'PK', 'BO', 'BLL', 'DR', 'CY', 'TU', 'CA', 'BJ', 'CJ', 'BE',
        'CT', 'PF', 'PG', '4A', 'RM', 'ST', 'LEF',
        // Energía
        'KWH', 'MWH',
    ],

];
