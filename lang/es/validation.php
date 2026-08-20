<?php

/**
 * Mensajes de validación en español.
 *
 * Solo están las reglas que el proyecto usa, y hay una prueba que falla si
 * aparece una regla nueva sin su mensaje: sin ella, la regla caería al idioma
 * de reserva y el usuario vería una frase en inglés correcta y creíble, que es
 * la peor forma de este hueco porque no se distingue de un mensaje bien
 * traducido.
 *
 * `attributes` es lo que evita que el mensaje nombre al campo por su
 * identificador. «El campo codigo no tiene un formato válido» es español y
 * sigue siendo jerga; el contrato pide texto sin jerga técnica.
 */
return [
    'array' => 'El campo :attribute debe ser una lista.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'in' => 'El valor de :attribute no está entre las opciones permitidas.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'regex' => 'El campo :attribute no tiene un formato válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',

    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],

    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],

    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],

    'custom' => [],

    /**
     * Nombre legible de cada campo, tal como lo llamaría quien opera el
     * sistema. Hay una prueba que compara estas claves con los campos que los
     * servicios validan de verdad.
     */
    'attributes' => [
        'activo' => 'estado',
        'cantidad' => 'cantidad',
        'categoria_id' => 'categoría',
        'cliente_id' => 'cliente',
        'codigo' => 'código',
        'codigo_lote' => 'código de lote',
        'costo_unitario' => 'costo unitario',
        'descripcion' => 'descripción',
        'direccion' => 'dirección',
        'email' => 'correo',
        'fecha_emision' => 'fecha de emisión',
        'fecha_vencimiento' => 'fecha de vencimiento',
        'metodo_pago' => 'método de pago',
        'nombre' => 'nombre',
        'numero_documento' => 'número de documento',
        'password' => 'contraseña',
        'precio_mayor' => 'precio al por mayor',
        'precio_menor' => 'precio al por menor',
        'producto_id' => 'producto',
        'proveedor_id' => 'proveedor',
        'razon_social' => 'razón social',
        'rol' => 'rol',
        'serie_documento' => 'serie del documento',
        'stock_minimo' => 'stock mínimo',
        'telefono' => 'teléfono',
        'tipo_comprobante' => 'tipo de comprobante',
        'tipo_documento' => 'tipo de documento',
        'tipo_precio' => 'tipo de precio',
        'unidad_medida' => 'unidad de medida',
    ],
];
