<?php

namespace App\Repositories;

use App\Models\Users;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    // Lista todos os usuários cadastrados.
    public function listarTodos(): Collection
    {
        return Users::all();
    }

    // Busca um usuário pelo ID.
    public function buscarPorId(int $id): ?Users
    {
        return Users::find($id);
    }

    // Cria um novo usuário.
    public function criar(array $dados): Users
    {
        return Users::create($dados);
    }

    // Atualiza os dados de um usuário existente.
    public function atualizar(int $id, array $dados): bool
    {
        return Users::where('id', $id)->update($dados);
    }

    // Remove um usuário pelo ID.
    public function deletar(int $id): bool
    {
        return Users::where('id', $id)->delete();
    }

    // Busca um usuário pelo nome de usuário (name_user).
    public function buscarPorNomeUser(string $nameUser): ?Users
    {
        return Users::where('name_user', $nameUser)->first();
    }
}