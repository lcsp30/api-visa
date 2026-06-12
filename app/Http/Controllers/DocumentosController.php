<?php

namespace App\Http\Controllers;

use App\Services\DocumentosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentosController extends Controller
{
    public function __construct(
        private DocumentosService $documentosService
    ) {}

    /**
     * Lista documentos de um estabelecimento filtrando por ID, ano e tipo (CNPJ/CPF).
     *
     * @param Request $request Contém 'id', 'ano' e 'tipo'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $data = $this->documentosService->listarDocumentos($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar documentos.'], 500);
        }
    }

    /**
     * Lista os anos disponíveis de documentos para um estabelecimento.
     *
     * @param Request $request Contém 'id' e 'tipo'.
     * @param mixed $id ID do estabelecimento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $request->merge(['id' => $id]);
            $data = $this->documentosService->listarAnosDisponiveis($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::show: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar anos disponíveis.'], 500);
        }
    }

    /**
     * Anexa um arquivo (PDF, DOC, DOCX, JPG, PNG) a um documento existente.
     *
     * @param Request $request Contém 'arquivo' (file), 'id' (documento) e 'tipo' (CNPJ/CPF).
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $data = $this->documentosService->anexarDocumento($request);
            return response()->json($data, 201);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao anexar documento.'], 500);
        }
    }

    /**
     * Altera o status de documentos em lote (via service).
     *
     * @param Request $request Contém 'dados' (array de itens) e 'tipo' (CNPJ/CPF).
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $data = $this->documentosService->alterarStatusDocumentos($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao alterar status dos documentos.'], 500);
        }
    }

    /**
     * Altera status de documentos em lote (rota alternativa PUT /doc/alterar).
     *
     * @param Request $request Contém 'dados' (array de itens) e 'tipo' (CNPJ/CPF).
     * @return \Illuminate\Http\JsonResponse
     */
    public function alterarStatusDocumentos(Request $request)
    {
        try {
            $data = $this->documentosService->alterarStatusDocumentos($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::alterarStatusDocumentos: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao alterar status dos documentos.'], 500);
        }
    }

    /**
     * Remove o arquivo anexado de um documento (zera doc_local, data_doc e url).
     *
     * @param Request $request Contém 'id' (documento), 'url' (caminho do arquivo) e 'tipo'.
     * @param mixed $id Opcional, ID do documento.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id = null)
    {
        try {
            if ($id !== null) {
                $request->merge(['id' => $id]);
            }
            $data = $this->documentosService->apagarDocumento($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::destroy: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao apagar documento.'], 500);
        }
    }

    /**
     * Faz o download do arquivo anexado a um documento.
     *
     * @param Request $request Contém 'url' (caminho relativo em storage).
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function download(Request $request)
    {
        try {
            $caminho = $this->documentosService->downloadArquivo($request);
            return response()->download($caminho);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::download: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao fazer download.'], 500);
        }
    }

    /**
     * Cria os registros de documentos para um novo ano com base nos documentos do estabelecimento.
     *
     * @param Request $request Contém 'id', 'ano', 'id_categoria' e 'tipo'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function criarDocsNovoAno(Request $request)
    {
        try {
            $data = $this->documentosService->criarDocumentosNovoAno($request);
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::criarDocsNovoAno: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao criar documentos para o novo ano.'], 500);
        }
    }

    /**
     * Gera e faz download de um protocolo em Word (.docx) para o estabelecimento.
     *
     * @param Request $request Contém 'n', 'especificos', 'tipo' e 'validade'.
     * @param string $id ID do estabelecimento.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function gerarProtocolo(Request $request, string $id)
    {
        try {
            $caminho = $this->documentosService->gerarProtocolo($request, $id);
            return response()->download($caminho);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::gerarProtocolo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao gerar protocolo.'], 500);
        }
    }

    /**
     * Lista licenças do ano corrente agrupadas por divisão técnica (DCQA, DCSEP, DCDM, DCSHT).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLicencas()
    {
        try {
            $data = $this->documentosService->gerarLicencas();
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::getLicencas: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao listar licenças.'], 500);
        }
    }

    /**
     * Cria ou finaliza uma intimação/constatação para um estabelecimento.
     *
     * @param Request $request Contém 'id', 'estabelecimento_id', 'ano', 'tipo', 'tipoEstabelecimento', 'descricao', 'data_expiracao' e 'finalizar'.
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeIntimacaoConstatacao(Request $request)
    {
        try {
            $data = $this->documentosService->salvarIntimacaoConstatacao($request);
            return response()->json($data, 201);
        } catch (\Throwable $e) {
            Log::error('DocumentosController::storeIntimacaoConstatacao: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao salvar intimação/constatação.'], 500);
        }
    }
}