<?php

namespace App\Services;

use App\Repositories\EstabelecimentoNotificadosRepository;
use Illuminate\Http\Request;

class EstabelecimentoNotificadosService
{
    private EstabelecimentoNotificadosRepository $repository;

    public function __construct(EstabelecimentoNotificadosRepository $repository)
    {
        $this->repository = $repository;
    }

    // Lista todos os registros de estabelecimentos notificados.
    public function listarTodos()
    {
        return $this->repository->listarTodos();
    }

    // Cria um novo registro de estabelecimento notificado a partir dos dados da requisição.
    public function criar(Request $request)
    {
        $this->repository->criar($request->all());
    }

    // Remove um estabelecimento notificado pelo ID.
    public function deletar(int $id): void
    {
        $this->repository->deletar($id);
    }
}