<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    // Lista todos os usuários cadastrados.
    public function listarTodos(): Collection
    {
        return User::all();
    }

    // Busca um usuário pelo ID.
    public function buscarPorId(int $id): ?User
    {
        return User::find($id);
    }

    // Cria um novo usuário.
    public function criar(array $dados): User
    {
        return User::create($dados);
    }

    // Atualiza os dados de um usuário existente.
    public function atualizar(int $id, array $dados): bool
    {
        return User::where('id', $id)->update($dados);
    }

    // Remove um usuário pelo ID.
    public function deletar(int $id): bool
    {
        return User::where('id', $id)->delete();
    }

    // Busca um usuário pelo nome de usuário (name_user).
    public function buscarPorNomeUser(string $nameUser): ?User
    {
        return User::where('name_user', $nameUser)->first();
    }
}