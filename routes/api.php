<?php
use App\Http\Controllers\CategoriaCnpjController;
use App\Http\Controllers\CategoriaCpfController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\Estabelecimento_cnpjController;
use App\Http\Controllers\Estabelecimento_cpfController;
use App\Http\Controllers\Estabelecimento_notificadosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rotas públicas de autenticação (login e registro com throttle de 5 tentativas por minuto).
Route::post('login' , [UserController::class, 'login'])->middleware('throttle:5,1');
Route::post('register', [UserController::class, 'register'])->middleware('throttle:5,1');

// Rotas protegidas por autenticação Sanctum (token via cookie HTTP-Only).
Route::middleware('auth:sanctum')->group(function(){
// Autenticação e gerenciamento de usuários.
Route::get('me', [UserController::class, 'me']);
Route::post('logout', [UserController::class, 'logout']);
Route::apiResource('user', UserController::class);

// Estabelecimentos CPF: CRUD, requerimento, categorias, licenças e busca por ID.
Route::apiResource('estabelecimentos_cpf', Estabelecimento_cpfController::class)->except(['show']);
Route::get('gerar_requerimento/{id}', [Estabelecimento_cpfController::class, 'requerimento']);
Route::get('getCategorias_cpf', [Estabelecimento_cpfController::class, 'getCategorias']);
Route::get('licenca_cpf', [Estabelecimento_cpfController::class, 'licenca_cpf']);
Route::get('getEstabelecimentos_cpf/{id}', [Estabelecimento_cpfController::class, 'getEstabelecimentos_cpf']);

// Categorias CPF e Documentos da Categoria (gerenciamento admin).
Route::get('categorias_cpf', [CategoriaCpfController::class, 'index']);
Route::post('categorias_cpf', [CategoriaCpfController::class, 'store']);
Route::put('categorias_cpf/{id}', [CategoriaCpfController::class, 'update']);
Route::delete('categorias_cpf/{id}', [CategoriaCpfController::class, 'destroy']);
Route::post('categorias_cpf/{id}/documentos', [CategoriaCpfController::class, 'storeDocumento']);
Route::put('categorias_cpf/{id}/documentos', [CategoriaCpfController::class, 'updateDocumento']);
Route::delete('categorias_cpf/{id}/documentos', [CategoriaCpfController::class, 'destroyDocumento']);

// Documentos: CRUD, download, alteração de status em lote, intimações/constatações, criação de docs para novo ano, protocolo e listagem de licenças.
Route::apiResource('doc', DocumentosController::class);
Route::post('doc/adicionar', [DocumentosController::class, 'adicionarDocumento']);
Route::get('download', [DocumentosController::class, 'download']);
Route::put('doc/alterar', [DocumentosController::class, 'alterarStatusDocumentos']);
Route::post('intimacao_constatacao', [DocumentosController::class, 'storeIntimacaoConstatacao']);
Route::post('criarDocsNovoAno', [DocumentosController::class, 'criarDocsNovoAno']);
Route::get('gerarProtocolo/{id}' , [DocumentosController::class, 'gerarProtocolo']);
Route::get('getLicencas', [DocumentosController::class, 'getLicencas']);

// Estabelecimentos CNPJ: CRUD, requerimento, categorias, licenças e busca por ID.
Route::apiResource('estabelecimentos_cnpj', Estabelecimento_cnpjController::class)->except(['show']);
Route::get('gerar_requerimento_cnpj/{id}', [Estabelecimento_cnpjController::class, 'requerimento']);
Route::get('getCategorias', [Estabelecimento_cnpjController::class, 'getCategorias']);
Route::get('licenca', [Estabelecimento_cnpjController::class, 'licenca']);
Route::get('getEstabelecimentos_cnpj/{id}', [Estabelecimento_cnpjController::class, 'getEstabelecimentos_cnpj']);

// Categorias CNPJ e Documentos da Categoria (gerenciamento admin).
Route::get('categorias_cnpj', [CategoriaCnpjController::class, 'index']);
Route::post('categorias_cnpj', [CategoriaCnpjController::class, 'store']);
Route::put('categorias_cnpj/{id}', [CategoriaCnpjController::class, 'update']);
Route::delete('categorias_cnpj/{id}', [CategoriaCnpjController::class, 'destroy']);
Route::post('categorias_cnpj/{id}/documentos', [CategoriaCnpjController::class, 'storeDocumento']);
Route::put('categorias_cnpj/{id}/documentos', [CategoriaCnpjController::class, 'updateDocumento']);
Route::delete('categorias_cnpj/{id}/documentos', [CategoriaCnpjController::class, 'destroyDocumento']);

// Estabelecimentos notificados: CRUD (exceto show).
Route::apiResource('estabelecimentos_notificados', Estabelecimento_notificadosController::class)->except(['show']);

// Dashboard: métricas consolidadas de cadastros, licenças e notificados.
Route::get('metricas', [DashboardController::class, 'index']);
});