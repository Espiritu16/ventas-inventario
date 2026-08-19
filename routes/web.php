<?php

use Illuminate\Support\Facades\Route;

/*
 * Las rutas de la aplicación se agregan desde S-01-F, junto con las pantallas.
 * Aquí solo vive la raíz, que confirma que el layout base y los assets
 * compilados se sirven.
 */
Route::get('/', fn () => view('inicio'))->name('inicio');
