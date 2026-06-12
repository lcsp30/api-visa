<?php

namespace App\Http\Controllers;

use App\Http\Requests\LicencaEstabelecimentoCpfRequest;
use App\Http\Requests\StoreEstabelecimentoCpfRequest;
use App\Http\Requests\UpdateEstabelecimentoCpfRequest;
use App\Http\Resources\Estabelecimento_cpfResouser;
use App\Models\Categorias_cpf;
use App\Models\Estabelecimentos_cpf;
use App\Services\EstabelecimentoCpfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Estabelecimento_cpfController extends Controller
{
    private EstabelecimentoCpfService $service;

    public function __construct(EstabelecimentoCpfService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista todos os estabelecimentos CPF com licenças, status e situação atualizada.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            return Estabelecimento_cpfResouser::collection($this->service->listarTodosEsbtCpf());
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar estabelecimentos.'], 500);
        }
    }

    /**
     * Busca um estabelecimento CPF pelo ID informado na query string.
     *
     * @param Request $request Contém 'id' do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEstabelecimentos_cpf(Request $request)
    {
        try {
            return response()->json(Estabelecimentos_cpf::findOrFail($request->id));
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::getEstabelecimentos_cpf: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Estabelecimento não encontrado.'], 404);
        }
    }

    /**
     * Lista todas as categorias disponíveis para CPF.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategorias()
    {
        try {
            return response()->json(Categorias_cpf::all());
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::getCategorias: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao buscar categorias.'], 500);
        }
    }

    /**
     * Cadastra um novo estabelecimento CPF e gera seus documentos iniciais do ano corrente.
     *
     * @param StoreEstabelecimentoCpfRequest $request Dados validados do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEstabelecimentoCpfRequest $request)
    {
        try {
            $estabelecimento = $this->service->criarEstabelecimentoComDocs($request->validated());
            return response()->json(['estabelecimento' => $estabelecimento], 201);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao cadastrar estabelecimento.'], 500);
        }
    }

    /**
     * Gera e faz download da licença de funcionamento (Word) para um estabelecimento CPF.
     *
     * @param LicencaEstabelecimentoCpfRequest $request Dados validados: 'id', 'tipo', 'categoria', 'ano'.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function licenca_cpf(LicencaEstabelecimentoCpfRequest $request)
    {
        try {
            $caminhoDownload = $this->service->baixarLicenca($request->validated());
            return response()->download($caminhoDownload);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::licenca_cpf: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao gerar licença.'], 500);
        }
    }

    /**
     * Gera e faz download de um requerimento em Word (.docx) para um estabelecimento CPF.
     *
     * @param Request $request Contém 'dados' (array de itens selecionados) e 'categoria'.
     * @param String $id ID do estabelecimento.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function requerimento(Request $request, String $id)
    {
        $categoria   = $request->categoria;
        $dadosInputs = $request->input('dados', []);
        if (!is_array($dadosInputs) || empty($dadosInputs)) {
            return response()->json(['Error' => 'Selecione ao menos um item para o requerimento.'], 422);
        }
        try {
            $caminho = $this->service->gerarRequerimento($dadosInputs, (int) $id, $categoria);
            return response()->download($caminho, 'Requerimento_' . $id . '.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::requerimento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao gerar requerimento.'], 500);
        }
    }

    /**
     * Atualiza os dados de um estabelecimento CPF.
     *
     * @param UpdateEstabelecimentoCpfRequest $request Dados validados para atualização.
     * @param string $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEstabelecimentoCpfRequest $request, string $id)
    {
        try {
            Estabelecimentos_cpf::where('id', $id)->update($request->validated());
            return response()->json(['res' => "Atualizado com Sucesso!"]);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao atualizar estabelecimento.'], 500);
        }
    }

    /**
     * Remove um estabelecimento CPF pelo ID.
     *
     * @param string $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            Estabelecimentos_cpf::where('id', $id)->delete();
            return response()->json(['res' => 'Estabelecimento removido com sucesso!']);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cpfController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao remover estabelecimento.'], 500);
        }
    }
}