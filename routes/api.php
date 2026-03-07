<?php

use App\Http\Controllers\Documentos_cpfController;
use App\Http\Controllers\Estabelecimento_cpfController;
use Illuminate\Support\Facades\Route;


Route::apiResource('estabelecimentos_cpf', Estabelecimento_cpfController::class);

Route::apiResource('doc', Documentos_cpfController::class);

Route::get('download', [Documentos_cpfController::class, 'download']);