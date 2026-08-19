<?php

use App\Dominios\Usuarios\Controllers\SesionController;
use App\Dominios\Usuarios\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas HTTP del servidor.
 *
 * Las pantallas viven en routes/web.php y son del frente de interfaz; acá
 * están las operaciones. Los dos archivos comparten el grupo `web`, así que
 * el control de acceso deny-by-default (RNF-013) los alcanza por igual: una
 * ruta sin fila en docs/requisitos/actores-permisos.md se rechaza, esté
 * declarada donde esté.
 */

Route::post('/login', [SesionController::class, 'iniciar'])->name('login');
Route::post('/logout', [SesionController::class, 'cerrar'])->name('logout');

Route::get('/usuarios', [UsuarioController::class, 'listar'])->name('usuarios.listar');
Route::post('/usuarios', [UsuarioController::class, 'crear'])->name('usuarios.crear');
Route::patch('/usuarios/{id}', [UsuarioController::class, 'actualizar'])->name('usuarios.actualizar');
