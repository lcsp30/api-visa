<?php

namespace App\Repositories;

use App\Models\Estabelecimentos_notificados;
use Illuminate\Database\Eloquent\Collection;

class EstabelecimentoNotificadosRepository
{
    // Lista todos os registros de estabelecimentos notificados.
    public function listarTodos(): Collection
    {
        return Estabelecimentos_notificados::all();
    }

    // Cria um novo registro de estabelecimento notificado.
    public function criar(array $dados): Estabelecimentos_notificados
    {
        return Estabelecimentos_notificados::create($dados);
    }

    // Busca um estabelecimento notificado pelo ID.
    public function buscarPorId(int $id): ?Estabelecimentos_notificados
    {
        return Estabelecimentos_notificados::find($id);
    }

    // Remove um estabelecimento notificado pelo ID.
    public function deletar(int $id): bool
    {
        $registro = Estabelecimentos_notificados::findOrFail($id);
        return $registro->delete();
    }
}