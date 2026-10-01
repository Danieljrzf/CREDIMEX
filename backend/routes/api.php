<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->group(function (): void {
    Route::get('/clientes', [ClienteController::class, 'index'])->middleware('permiso:clientes.consultar');
    Route::post('/clientes', [ClienteController::class, 'store'])->middleware('permiso:clientes.registrar');
    Route::get('/clientes/{id_publico}', [ClienteController::class, 'show'])
        ->middleware('permiso:clientes.consultar')
        ->whereUuid('id_publico');
    Route::patch('/clientes/{id_publico}', [ClienteController::class, 'update'])
        ->middleware('permiso:clientes.editar')
        ->whereUuid('id_publico');
});
