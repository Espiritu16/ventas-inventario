<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * Pantallas del sistema.
 *
 * Acá viven las vistas; las operaciones HTTP de sesión están en
 * routes/backend.php. Cada ruta de acá está declarada en
 * docs/requisitos/actores-permisos.md y transcrita a la matriz de permisos.
 * El control lo aplica el middleware global: una pantalla sin fila se
 * rechaza sola (RNF-013).
 */

Route::get('/', fn () => view('inicio'))->name('inicio');

Route::get('/login', fn () => Auth::check() ? redirect('/panel') : view('acceso'))
    ->name('acceso');

Route::get('/panel', fn () => view('panel'))->name('panel');
