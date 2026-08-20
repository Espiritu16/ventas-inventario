<?php

use App\Dominios\Catalogo\Controllers\CategoriaController;
use App\Dominios\Catalogo\Controllers\ProductoController;
use App\Dominios\Usuarios\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas HTTP del servidor.
 *
 * Solo viven acá las operaciones que el navegador ejecuta de verdad contra el
 * servidor. La gestión de usuarios NO está: son pantallas Livewire que
 * invocan `UsuarioService` en el mismo proceso, sin cliente HTTP de por medio
 * (ADR-0005 y la enmienda de 2026-08-19 en docs/contratos/usuarios.md).
 *
 * Las pantallas viven en routes/web.php y son del frente de interfaz; acá
 * están las operaciones. Los dos archivos comparten el grupo `web`, así que
 * el control de acceso deny-by-default (RNF-013) los alcanza por igual: una
 * ruta sin fila en docs/requisitos/actores-permisos.md se rechaza, esté
 * declarada donde esté.
 */

Route::post('/login', [SesionController::class, 'iniciar'])->name('login');
Route::post('/logout', [SesionController::class, 'cerrar'])->name('logout');

Route::get('/categorias', [CategoriaController::class, 'listar'])->name('categorias.listar');
Route::post('/categorias', [CategoriaController::class, 'crear'])->name('categorias.crear');
Route::patch('/categorias/{id}', [CategoriaController::class, 'actualizar'])->name('categorias.actualizar');

Route::get('/productos', [ProductoController::class, 'listar'])->name('productos.listar');
Route::post('/productos', [ProductoController::class, 'crear'])->name('productos.crear');
Route::get('/productos/{id}', [ProductoController::class, 'ver'])->name('productos.ver');
Route::patch('/productos/{id}', [ProductoController::class, 'actualizar'])->name('productos.actualizar');
