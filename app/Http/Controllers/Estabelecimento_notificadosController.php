<?php

namespace App\Http\Controllers;

use App\Services\EstabelecimentoNotificadosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Estabelecimento_notificadosController extends Controller
{
    public function __construct(
        private EstabelecimentoNotificadosService $notificadosService
    ) {}

    /**
     * Lista todos os estabelecimentos notificados.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            return response()->json($this->notificadosService->listarTodos());
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_notificadosController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar notificados.'], 500);
        }
    }

    /**
     * Cria uma nova notificação para um estabelecimento.
     *
     * @param Request $request Dados da notificação.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $this->notificadosService->criar($request);
            return response()->json(['Menssagem' => 'Sucesso criado com Sucesso!!']);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_notificadosController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao criar notificação.'], 500);
        }
    }

    /**
     * Remove uma notificação pelo ID.
     *
     * @param string $id ID da notificação.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $this->notificadosService->deletar((int) $id);
            return response()->json(['message' => 'Excluído com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_notificadosController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao excluir notificação.'], 500);
        }
    }
}