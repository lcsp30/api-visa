<?php
namespace App\Http\Controllers;

use App\Http\Requests\LicencaEstabelecimentoCnpjRequest;
use App\Http\Requests\StoreEstabelecimentoCnpjRequest;
use App\Http\Requests\UpdateEstabelecimentoCnpjRequest;
use App\Http\Resources\EstabelecimentoCnpjResource;
use App\Models\Categorias;
use App\Models\Estabelecimentos_cnpj;
use App\Services\EstabelecimentoCnpjService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Estabelecimento_cnpjController extends Controller
{
    public function __construct(
        private EstabelecimentoCnpjService $service
    ) {}

    /**
     * Lista os estabelecimentos CNPJ paginados (70/página) com busca opcional por LIKE.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $busca = $request->query('busca', '');
            return EstabelecimentoCnpjResource::collection($this->service->listarTodosEsbtCnpj($busca));
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar estabelecimentos.'], 500);
        }
    }

    /**
     * Busca um estabelecimento CNPJ pelo ID.
     *
     * @param String $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEstabelecimentos_cnpj(String $id)
    {
        try {
            $dados = Estabelecimentos_cnpj::findOrFail($id);
            return response()->json($dados);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::getEstabelecimentos_cnpj: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Estabelecimento não encontrado.'], 404);
        }
    }

    /**
     * Lista todas as categorias disponíveis para CNPJ.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategorias()
    {
        try {
            return response()->json(Categorias::all());
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::getCategorias: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao buscar categorias.'], 500);
        }
    }

    /**
     * Cadastra um novo estabelecimento CNPJ e gera seus documentos iniciais do ano corrente.
     *
     * @param StoreEstabelecimentoCnpjRequest $request Dados validados do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEstabelecimentoCnpjRequest $request)
    {
        try {
            $estabelecimento = $this->service->criarEstabelecimentoComDocs($request->validated());
            return response()->json(['estabelecimento' => $estabelecimento], 201);
        } catch (QueryException $e) {
            // Defesa em profundidade: violação de UNIQUE (ex.: corrida entre submissões).
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json(['errors' => ['cnpj' => ['Este CNPJ já está cadastrado.']]], 422);
            }
            Log::error('Estabelecimento_cnpjController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao cadastrar estabelecimento.'], 500);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao cadastrar estabelecimento.'], 500);
        }
    }

    /**
     * Gera e faz download da licença de funcionamento (Word) para um estabelecimento CNPJ.
     *
     * @param LicencaEstabelecimentoCnpjRequest $request Dados validados: 'id', 'tipo', 'categoria', 'ano'.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function licenca(LicencaEstabelecimentoCnpjRequest $request)
    {
        try {
            $caminhoDownload = $this->service->baixarLicenca($request->validated());
            return response()->download($caminhoDownload);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::licenca: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao gerar licença.'], 500);
        }
    }

    /**
     * Gera e faz download de um requerimento em Word (.docx) para um estabelecimento CNPJ.
     *
     * @param Request $request Contém 'dados' (array de itens selecionados).
     * @param String $id ID do estabelecimento.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function requerimento(Request $request, String $id)
    {
        $dadosInputs = $request->input('dados', []);
        if (!is_array($dadosInputs) || empty($dadosInputs)) {
            return response()->json(['Error' => 'Selecione ao menos um item para o requerimento.'], 422);
        }
        try {
            $caminho = $this->service->gerarRequerimento($dadosInputs, (int) $id);
            return response()->download($caminho, 'Requerimento_' . $id . '.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::requerimento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao gerar requerimento.'], 500);
        }
    }

    /**
     * Atualiza os dados de um estabelecimento CNPJ.
     *
     * @param UpdateEstabelecimentoCnpjRequest $request Dados validados para atualização.
     * @param string $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEstabelecimentoCnpjRequest $request, string $id)
    {
        try {
            Estabelecimentos_cnpj::where('id', $id)->update($request->validated());
            return response()->json(['res' => "Atualizado com Sucesso!"]);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao atualizar estabelecimento.'], 500);
        }
    }

    /**
     * Remove um estabelecimento CNPJ pelo ID.
     *
     * @param string $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            Estabelecimentos_cnpj::where('id', $id)->delete();
            return response()->json(['res' => 'Estabelecimento removido com sucesso!']);
        } catch (\Throwable $e) {
            Log::error('Estabelecimento_cnpjController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao remover estabelecimento.'], 500);
        }
    }
}