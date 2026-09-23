<?php

/*
|--------------------------------------------------------------------------
| Nombres visibles de las categorías
|--------------------------------------------------------------------------
|
| Están aquí y no en el enum para que cambien con el idioma del perfil. Las
| claves son los valores de App\Enums\CategoriaMovimiento y no se traducen:
| son lo que se guarda en la base de datos.
|
*/

return [

    // Ingresos
    'salario' => 'Salario',
    'trabajo_independiente' => 'Trabajo independiente',
    'venta' => 'Venta',
    'regalo' => 'Regalo',
    'reembolso' => 'Reembolso',
    'intereses' => 'Intereses',

    // Gastos
    'alimentacion' => 'Alimentación',
    'transporte' => 'Transporte',
    'vivienda' => 'Vivienda',
    'servicios' => 'Servicios',
    'salud' => 'Salud',
    // "Ocio" y no "Entretenimiento": es lo que ya enseñaba la app, y cabe en
    // las fichas de categoría sin partirse en dos líneas.
    'entretenimiento' => 'Ocio',
    'compras' => 'Compras',

    // De los dos
    'otros' => 'Otros',

];
