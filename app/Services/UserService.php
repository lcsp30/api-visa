<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    // Lista todos os usuários cadastrados.
    public function listarTodos()
    {
        return $this->repository->listarTodos();
    }

    // Registra um novo usuário com senha criptografada e retorna o usuário + token de acesso.
    public function registrar(Request $request): array
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'password'  => 'required|min:8|confirmed',
            'name_user' => 'required|string|max:50',
        ]);

        $user = $this->repository->criar([
            'name'      => $request->name,
            'name_user' => $request->name_user,
            'password'  => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Autentica por name_user e senha, distinguindo usuário inexistente de senha incorreta.
    public function login(Request $request): array
    {
        $request->validate([
            'name_user' => 'required',
            'password'  => 'required',
        ]);

        $user = $this->repository->buscarPorNomeUser($request->name_user);

        if (!$user) {
            throw ValidationException::withMessages([
                'name_user' => ['Usuário incorreto ou não existente.'],
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Senha Incorreta.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Revoga o token de acesso atual do usuário autenticado.
    public function logout(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }

    // Cria um novo usuário com nível de acesso (admin).
    public function criar(Request $request): \App\Models\Users
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'password'     => 'required|min:8',
            'name_user'    => 'required|string|max:50|unique:users,name_user',
            'nivel_acesso' => 'required|integer|in:1,2',
        ]);

        return $this->repository->criar([
            'name'         => $request->name,
            'name_user'    => $request->name_user,
            'password'     => Hash::make($request->password),
            'nivel_acesso' => $request->nivel_acesso,
        ]);
    }

    // Atualiza os dados de um usuário existente (opcionalmente a senha).
    public function atualizar(Request $request, int $id): void
    {
        $rules = [
            'name'      => 'sometimes|string|max:255',
            'name_user' => 'sometimes|string|max:50|unique:users,name_user,' . $id,
            'nivel_acesso' => 'sometimes|integer|in:1,2',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'min:8';
        }

        $request->validate($rules);

        $dados = $request->only(['name', 'name_user', 'nivel_acesso']);

        if ($request->filled('password')) {
            $dados['password'] = Hash::make($request->password);
        }

        $this->repository->atualizar($id, $dados);
    }

    // Remove um usuário pelo ID.
    public function deletar(int $id): void
    {
        $this->repository->deletar($id);
    }
}