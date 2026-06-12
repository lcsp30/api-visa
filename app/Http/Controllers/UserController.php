<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Lista todos os usuários cadastrados.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            return UserResource::collection($this->userService->listarTodos());
        } catch (\Throwable $e) {
            Log::error('UserController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar usuários.'], 500);
        }
    }

    /**
     * Registra um novo usuário e retorna o usuário criado com token de acesso.
     *
     * @param Request $request Contém 'name', 'password', 'password_confirmation' e 'name_user'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $data = $this->userService->registrar($request);
            return response()->json($data, 201);
        } catch (\Throwable $e) {
            Log::error('UserController::register: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao cadastrar usuário.'], 500);
        }
    }

    /**
     * Autentica um usuário e retorna o token de acesso.
     *
     * @param Request $request Contém 'name_user' e 'password'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $data = $this->userService->login($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('UserController::login: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao realizar login.'], 500);
        }
    }

    /**
     * Revoga o token de acesso atual do usuário autenticado.
     *
     * @param Request $request Requisição autenticada.
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $this->userService->logout($request);
            return response()->json(['message' => 'Logout realizado com sucesso.']);
        } catch (\Throwable $e) {
            Log::error('UserController::logout: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao realizar logout.'], 500);
        }
    }

    /**
     * Cria um novo usuário (rota admin).
     *
     * @param Request $request Contém 'name', 'password', 'name_user' e 'nivel_acesso'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = $this->userService->criar($request);
            return response()->json(new UserResource($user), 201);
        } catch (\Throwable $e) {
            Log::error('UserController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao criar usuário.'], 500);
        }
    }

    /**
     * Atualiza os dados de um usuário existente.
     *
     * @param Request $request Dados a serem atualizados.
     * @param string $id ID do usuário.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            $this->userService->atualizar($request, (int) $id);
            return response()->json(['message' => 'Usuário atualizado com sucesso.']);
        } catch (\Throwable $e) {
            Log::error('UserController::update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    /**
     * Remove um usuário pelo ID.
     *
     * @param string $id ID do usuário.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $this->userService->deletar((int) $id);
            return response()->json(['message' => 'Usuário removido com sucesso.']);
        } catch (\Throwable $e) {
            Log::error('UserController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao remover usuário.'], 500);
        }
    }
}