<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unidades de medida — subconjunto adoptado
    |--------------------------------------------------------------------------
    |
    | Códigos que pueden viajar en el comprobante electrónico. SUNAT rechaza el
    | documento entero si recibe uno que su catálogo no admite, así que la
    | validación ocurre al crear el producto y no al emitir: descubrirlo recién
    | en la caja significaría una venta que no se puede facturar.
    |
    | El Catálogo 03 de SUNAT no enumera códigos: delega en UN/ECE
    | Recommendation 20 Rev 13, que tiene del orden de mil ochocientos. Esta
    | lista es un SUBCONJUNTO que adopta el proyecto —decisión nuestra, no un
    | hecho sobre SUNAT—, porque aceptar los mil ochocientos volvería inútil la
    | validación: existe para que un error de carga no llegue al comprobante, y
    | una comercializadora no vende en unidades astronómicas.
    |
    | Ampliarlo cuando el negocio lo pida es trivial: agregar el código acá.
    |
    | Estado: PROPUESTA pendiente de aprobación de Arquitectura. Los códigos
    | provienen de una guía de referencia para Perú y NO se pudieron contrastar
    | contra Rec 20: el archivo de UN/ECE devuelve 403 y los anexos de SUNAT no
    | fueron legibles desde acá. Ver el handoff de S-02-B.
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
