<?php

namespace App\Http\Controllers;

use App\Models\Categorias;
use App\Models\Doc_categoria;
use App\Models\Estabelecimentos_cnpj;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CategoriaCnpjController extends Controller
{
    /**
     * Verifica se o usuário autenticado possui perfil de Administrador (nivel_acesso = 1).
     */
    private function verificarAdmin(Request $request): ?JsonResponse
    {
        if ((int) $request->user()?->nivel_acesso !== 1) {
            return response()->json(['Error' => 'Acesso restrito a administradores.'], 403);
        }
        return null;
    }

    /**
     * Lista todas as categorias CNPJ com seus documentos (doc_categoria) e contagem de estabelecimentos vinculados.
     */
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        try {
            $categorias = Categorias::with('doc_categoria')
                ->withCount('estabelecimentos')
                ->orderBy('id_categoria', 'asc')
                ->get();

            return response()->json($categorias);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar categorias CNPJ.'], 500);
        }
    }

    /**
     * Cria uma nova categoria CNPJ e adiciona automaticamente 'Requerimento' e 'Licença de Funcionamento'.
     */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'nome_categoria' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categorias', 'nome_categoria'),
            ],
        ], [
            'nome_categoria.required' => 'Informe o nome da categoria.',
            'nome_categoria.max' => 'O nome da categoria deve ter no máximo 150 caracteres.',
            'nome_categoria.unique' => 'Já existe uma categoria CNPJ com este nome.',
        ]);

        try {
            $categoria = DB::transaction(function () use ($validated) {
                $novaCategoria = Categorias::create([
                    'nome_categoria' => trim($validated['nome_categoria']),
                ]);

                $docsPadrao = [
                    ['categoria_id' => $novaCategoria->id_categoria, 'nome_doc' => 'Requerimento', 'fixo' => 0],
                    ['categoria_id' => $novaCategoria->id_categoria, 'nome_doc' => 'Licença de Funcionamento', 'fixo' => 0],
                ];

                foreach ($docsPadrao as $doc) {
                    Doc_categoria::create($doc);
                }

                return $novaCategoria->load('doc_categoria')->loadCount('estabelecimentos');
            });

            return response()->json([
                'message' => 'Categoria criada com sucesso!',
                'categoria' => $categoria,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao criar categoria CNPJ.'], 500);
        }
    }

    /**
     * Atualiza o nome de uma categoria CNPJ existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['Error' => 'Categoria não encontrada.'], 404);
        }

        $validated = $request->validate([
            'nome_categoria' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categorias', 'nome_categoria')->ignore($id, 'id_categoria'),
            ],
        ], [
            'nome_categoria.required' => 'Informe o nome da categoria.',
            'nome_categoria.max' => 'O nome da categoria deve ter no máximo 150 caracteres.',
            'nome_categoria.unique' => 'Já existe uma categoria CNPJ com este nome.',
        ]);

        try {
            $categoria->update([
                'nome_categoria' => trim($validated['nome_categoria']),
            ]);

            return response()->json([
                'message' => 'Categoria atualizada com sucesso!',
                'categoria' => $categoria->fresh(['doc_categoria'])->loadCount('estabelecimentos'),
            ]);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao atualizar categoria CNPJ.'], 500);
        }
    }

    /**
     * Exclui uma categoria CNPJ e seus documentos caso não possua estabelecimentos vinculados.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['Error' => 'Categoria não encontrada.'], 404);
        }

        $possuiEstabelecimentos = Estabelecimentos_cnpj::where('categoria_id', $id)->exists();
        if ($possuiEstabelecimentos) {
            return response()->json([
                'Error' => 'Não é possível excluir esta categoria pois existem estabelecimentos vinculados a ela.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($id, $categoria) {
                Doc_categoria::where('categoria_id', $id)->delete();
                $categoria->delete();
            });

            return response()->json([
                'message' => 'Categoria excluída com sucesso!',
            ]);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao excluir categoria CNPJ.'], 500);
        }
    }

    /**
     * Adiciona um novo documento (doc_categoria) a uma categoria CNPJ existente.
     */
    public function storeDocumento(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['Error' => 'Categoria não encontrada.'], 404);
        }

        $validated = $request->validate([
            'nome_doc' => 'required|string|max:200',
            'fixo' => 'required|boolean',
        ], [
            'nome_doc.required' => 'Informe o nome do documento.',
            'nome_doc.max' => 'O nome do documento deve ter no máximo 200 caracteres.',
        ]);

        $nomeDoc = trim($validated['nome_doc']);

        $jaExiste = Doc_categoria::where('categoria_id', $id)
            ->whereRaw('LOWER(nome_doc) = ?', [mb_strtolower($nomeDoc, 'UTF-8')])
            ->exists();

        if ($jaExiste) {
            return response()->json([
                'Error' => 'Este documento já está cadastrado nesta categoria.',
            ], 422);
        }

        try {
            Doc_categoria::create([
                'categoria_id' => $id,
                'nome_doc' => $nomeDoc,
                'fixo' => $validated['fixo'] ? 1 : 0,
            ]);

            return response()->json([
                'message' => 'Documento adicionado com sucesso!',
                'documentos' => Doc_categoria::where('categoria_id', $id)->get(),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::storeDocumento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao adicionar documento à categoria.'], 500);
        }
    }

    /**
     * Atualiza o nome e/ou indicador 'fixo' de um documento existente em doc_categoria.
     */
    public function updateDocumento(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['Error' => 'Categoria não encontrada.'], 404);
        }

        $validated = $request->validate([
            'nome_doc_original' => 'required|string|max:200',
            'nome_doc' => 'required|string|max:200',
            'fixo' => 'required|boolean',
        ], [
            'nome_doc.required' => 'Informe o nome do documento.',
            'nome_doc.max' => 'O nome do documento deve ter no máximo 200 caracteres.',
        ]);

        $nomeOriginal = $validated['nome_doc_original'];
        $novoNome = trim($validated['nome_doc']);

        $existeOriginal = Doc_categoria::where('categoria_id', $id)
            ->where('nome_doc', $nomeOriginal)
            ->exists();

        if (!$existeOriginal) {
            return response()->json(['Error' => 'Documento original não encontrado nesta categoria.'], 404);
        }

        if (mb_strtolower($nomeOriginal, 'UTF-8') !== mb_strtolower($novoNome, 'UTF-8')) {
            $duplicado = Doc_categoria::where('categoria_id', $id)
                ->whereRaw('LOWER(nome_doc) = ?', [mb_strtolower($novoNome, 'UTF-8')])
                ->exists();

            if ($duplicado) {
                return response()->json([
                    'Error' => 'Já existe outro documento com este nome nesta categoria.',
                ], 422);
            }
        }

        try {
            DB::table('doc_categoria')
                ->where('categoria_id', $id)
                ->where('nome_doc', $nomeOriginal)
                ->limit(1)
                ->update([
                    'nome_doc' => $novoNome,
                    'fixo' => $validated['fixo'] ? 1 : 0,
                ]);

            return response()->json([
                'message' => 'Documento atualizado com sucesso!',
                'documentos' => Doc_categoria::where('categoria_id', $id)->get(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::updateDocumento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao atualizar documento da categoria.'], 500);
        }
    }

    /**
     * Remove um documento de doc_categoria pelo categoria_id e nome_doc.
     */
    public function destroyDocumento(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->verificarAdmin($request)) {
            return $denied;
        }

        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['Error' => 'Categoria não encontrada.'], 404);
        }

        $nomeDoc = $request->input('nome_doc') ?? $request->query('nome_doc');
        if (!$nomeDoc) {
            return response()->json(['Error' => 'Informe o nome do documento a ser removido.'], 422);
        }

        try {
            $removidos = DB::table('doc_categoria')
                ->where('categoria_id', $id)
                ->where('nome_doc', $nomeDoc)
                ->limit(1)
                ->delete();

            if ($removidos === 0) {
                return response()->json(['Error' => 'Documento não encontrado nesta categoria.'], 404);
            }

            return response()->json([
                'message' => 'Documento removido com sucesso!',
                'documentos' => Doc_categoria::where('categoria_id', $id)->get(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CategoriaCnpjController::destroyDocumento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao remover documento da categoria.'], 500);
        }
    }
}

