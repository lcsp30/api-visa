<?php

use App\Http\Controllers\Documentos_cpfController;
use App\Http\Controllers\Estabelecimento_cnpjController;
use App\Http\Controllers\Estabelecimento_cpfController;
use App\Http\Controllers\Estabelecimento_notificadosController;
use Illuminate\Support\Facades\Route;

Route::apiResource('estabelecimentos_cpf', Estabelecimento_cpfController::class);
Route::get('gerar_requerimento/{id}', [Estabelecimento_cpfController::class, 'requerimento']);
Route::get('getCategorias_cpf', [Estabelecimento_cpfController::class, 'getCategorias']);
Route::get('licenca_cpf', [Estabelecimento_cpfController::class, 'licenca_cpf']);
Route::get('getEstabelecimentos_cpf', [Estabelecimento_cpfController::class, 'getEstabelecimentos_cpf']);

Route::apiResource('doc', Documentos_cpfController::class);
Route::get('download', [Documentos_cpfController::class, 'download']);
Route::post('intimacao_constatacao', [Documentos_cpfController::class, 'storeIntimacaoConstatacao']);
Route::post('criarDocsNovoAno', [Documentos_cpfController::class, 'criarDocsNovoAno']);
Route::get('gerarProtocolo/{id}' , [Documentos_cpfController::class, 'gerarProtocolo']);
Route::get('getLicencas', [Documentos_cpfController::class, 'getLicencas']);

Route::apiResource('estabelecimentos_cnpj', Estabelecimento_cnpjController::class);
Route::get('gerar_requerimento_cnpj/{id}', [Estabelecimento_cnpjController::class, 'requerimento']);
Route::get('getCategorias', [Estabelecimento_cnpjController::class, 'getCategorias']);
Route::get('licenca', [Estabelecimento_cnpjController::class, 'licenca']);
Route::get('getEstabelecimentos_cnpj', [Estabelecimento_cnpjController::class, 'getEstabelecimentos_cnpj']);

Route::apiResource('estabelecimentos_notificados', Estabelecimento_notificadosController::class);