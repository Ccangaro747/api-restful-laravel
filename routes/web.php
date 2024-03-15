<?php

use Illuminate\Support\Facades\Route;
use App\Post;
use App\Category;

// Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;

/*
RUTAS DE PRUEBA
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pruebas/{nombre?}', function ($nombre = null) {
    $texto = '<h2>Texto desde una ruta</h2>';
    $texto .= 'Nombre: '.$nombre;
    return view('pruebas', array(
        'texto' => $texto
    ));
});

Route::get('/animales', 'App\Http\Controllers\PruebasController@index');

Route::get('/test-orm', 'App\Http\Controllers\PruebasController@testOrm');


/*
RUTAS API
*/


// Rutas de prueba
//Route::get('/user', 'App\Http\Controllers\UserController@pruebas');

//Route::get('/posts', 'App\Http\Controllers\PostController@pruebas');

//Route::get('/category', 'App\Http\Controllers\CategoryController@pruebas');

// Rutas del controlador de usuario

Route::post('/api/register', 'App\Http\Controllers\UserController@register');

Route::post('/api/login', 'App\Http\Controllers\UserController@login');

Route::put('/api/update', 'App\Http\Controllers\UserController@update');

Route::post('/api/update', 'App\Http\Controllers\UserController@update');

Route::post('/api/upload', 'App\Http\Controllers\UserController@upload')->middleware(ApiAuthMiddleware::class);

Route::get('/api/avatar/{filename}', 'App\Http\Controllers\UserController@getImage');

Route::get('/api/detail/{id}', 'App\Http\Controllers\UserController@detail');

// Rutas del controlador de categorias

Route::resource('/api/category', 'App\Http\Controllers\CategoryController');