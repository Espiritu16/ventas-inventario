<?php

use App\Dominios\Usuarios\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas HTTP del servidor.
 *
 * Solo viven acá las operaciones que el navegador ejecuta de verdad contra el
 * servidor. Los recursos de dominio NO están: se sirven como pantallas, y la
 * operación la ejecuta el servicio de dominio invocado en el mismo proceso por
 * el componente (ADR-0006).
 *
 * Un endpoint que ningún consumidor declarado usa no es una comodidad para el
 * futuro: es superficie viva que hay que autorizar, probar y mantener, y que
 * además se disputa la URI con la pantalla que sí existe.
 */

Route::post('/login', [SesionController::class, 'iniciar'])->name('login');
Route::post('/logout', [SesionController::class, 'cerrar'])->name('logout');
