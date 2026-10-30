<?php

namespace App\Services;

use App\Enums\TipoEstabelecimentoEnum;
use App\Helpers\FormatadorHelper;
use App\Repositories\DocumentosCnpjRepository;
use App\Repositories\DocumentosCpfRepository;
use App\Repositories\EstabelecimentoCnpjRepository;
use App\Repositories\EstabelecimentoCpfRepository;
use App\Repositories\IntimacaoConstatacaoRepository;
use App\Repositories\LicencaRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentosService
{
    public function __construct(
        private DocumentosCnpjRepository $documentosCnpjRepo,
        private DocumentosCpfRepository $documentosCpfRepo,
        private IntimacaoConstatacaoRepository $intimacaoRepo,
        private LicencaRepository $licencaRepo,
        private EstabelecimentoCnpjRepository $estabelecimentoCnpjRepo,
        private EstabelecimentoCpfRepository $estabelecimentoCpfRepo,
    ) {}

    // Lista documentos, licenças e intimações de um estabelecimento filtrando por ID, ano e tipo.
    public function listarDocumentos(Request $request): array
    {
        $id   = $request->id;
        $ano  = $request->ano;
        $tipo = $request->tipo;

        if (!$id || !$ano || !$tipo) {
            return ['Erro' => 'Dados faltando da Requisição!!'];
        }

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            return $this->listarDocumentosCnpj($id, $ano);
        }

        return $this->listarDocumentosCpf($id, $ano);
    }

    // Monta o payload de documentos, licença e intimações para estabelecimento CNPJ.
    private function listarDocumentosCnpj(int $id, int $ano): array
    {
        $todosDados = $this->documentosCnpjRepo->buscarPorEstabelecimentoEAno($id, $ano);

        $licenca = $todosDados->where('nome_doc', 'Licença de Funcionamento')->first();
        $dados   = $todosDados->reject(fn($item) => $item->nome_doc === 'Licença de Funcionamento')->values();

        $dadosAtivos   = $dados->where('status', '!=', 1)->values()->all();
        $docPendentes  = $dados->whereNull('doc_local')->values()->all();
        $docAnexados   = $dados->whereNotNull('doc_local')->values()->all();

        $dadosAuxiliares = \App\Models\Estabelecimentos_cnpj::query()
            ->select('id', 'situacao')
            ->withMax('documento', 'ano')
            ->with(['intimacoes' => function ($query) use ($ano) {
                $query->select('id_intimacao_constatacao', 'estabelecimento_id', 'tipo', 'descricao', 'data_inicial', 'data_expiracao', 'status')
                    ->where('ano', $ano);
            }])
            ->findOrFail($id);

        $maoirAno  = $dadosAuxiliares->documento_max_ano;
        $situacao  = $dadosAuxiliares->situacao;
        $dadosAgrupados = $dadosAuxiliares->intimacoes->groupBy('tipo');
        $anoAtual = false;

        if ($ano == $maoirAno) {
            $anoAtual = true;
            if ($licenca) {
                if (is_null($licenca->doc_local) && $situacao != 0) {
                    $this->estabelecimentoCnpjRepo->atualizarSituacaoCnpj($id, 0);
                } elseif (!is_null($licenca->doc_local) && $situacao != 1) {
                    $this->estabelecimentoCnpjRepo->atualizarSituacaoCnpj($id, 1);
                }
            }
        }

        return [
            'doc'          => $dados,
            'docAtivos'    => $dadosAtivos,
            'docPendentes' => $docPendentes,
            'docAnexados'  => $docAnexados,
            'licenca'      => $licenca,
            'intimacao'    => ($dadosAgrupados[1] ?? collect())->values(),
            'constatacao'  => ($dadosAgrupados[2] ?? collect())->values(),
            'anoAtual'     => $anoAtual,
        ];
    }

    // Monta o payload de documentos, licença e intimações para estabelecimento CPF.
    private function listarDocumentosCpf(int $id, int $ano): array
    {
        $todosDados = $this->documentosCpfRepo->buscarPorEstabelecimentoEAno($id, $ano);

        $licenca = $todosDados->where('nome_doc', 'Licença de Funcionamento')->first();
        $dados   = $todosDados->reject(fn($item) => $item->nome_doc === 'Licença de Funcionamento')->values();

        $dadosAtivos   = $dados->where('status', '!=', 1)->values()->all();
        $docPendentes  = $dados->whereNull('doc_local')->values()->all();
        $docAnexados   = $dados->whereNotNull('doc_local')->values()->all();

        $dadosAuxiliares = \App\Models\Estabelecimentos_cpf::query()
            ->select('id', 'situacao')
            ->withMax('documentos', 'ano')
            ->with(['intimacoes' => function ($query) use ($ano) {
                $query->select('id_intimacao_constatacao', 'estabelecimento_id', 'tipo', 'descricao', 'data_inicial', 'data_expiracao', 'status')
                    ->where('ano', $ano);
            }])
            ->findOrFail($id);

        $maoirAno  = $dadosAuxiliares->documentos_max_ano;
        $situacao  = $dadosAuxiliares->situacao;
        $dadosAgrupados = $dadosAuxiliares->intimacoes->groupBy('tipo');
        $anoAtual = false;

        if ($ano == $maoirAno) {
            $anoAtual = true;
            if ($licenca) {
                if (is_null($licenca->doc_local) && $situacao != 0) {
                    $this->estabelecimentoCpfRepo->atualizarSituacaoCpf($id, 0);
                } elseif (!is_null($licenca->doc_local) && $situacao != 1) {
                    $this->estabelecimentoCpfRepo->atualizarSituacaoCpf($id, 1);
                }
            }
        }

        return [
            'doc'          => $dados,
            'docAtivos'    => $dadosAtivos,
            'docPendentes' => $docPendentes,
            'docAnexados'  => $docAnexados,
            'licenca'      => $licenca,
            'intimacao'    => ($dadosAgrupados[1] ?? collect())->values(),
            'constatacao'  => ($dadosAgrupados[2] ?? collect())->values(),
            'anoAtual'     => $anoAtual,
        ];
    }

    // Anexa um arquivo (PDF, DOC, DOCX, JPG, PNG até 10MB) a um documento CNPJ ou CPF.
    public function anexarDocumento(Request $request): array
    {
        if (!$request->hasFile('arquivo')) {
            throw new \RuntimeException('Nenhum arquivo enviado');
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            throw new \RuntimeException('Arquivo inválido. Permitidos apenas PDF, DOC, DOCX, JPG e PNG (máx. 10MB).');
        }

        $arquivo = $request->file('arquivo')->store('documentos/docs_salvos', 'public');
        $id      = $request->id;
        $tipo    = $request->tipo;

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            $this->documentosCnpjRepo->atualizarPorIdDocumento($id, [
                'doc_local' => $arquivo,
                'data_doc'  => date('Y-m-d'),
                'url'       => asset('storage/' . $arquivo),
            ]);
        } else {
            $this->documentosCpfRepo->atualizarPorIdDocumento($id, [
                'doc_local' => $arquivo,
                'data_doc'  => date('Y-m-d'),
                'url'       => asset('storage/' . $arquivo),
            ]);
        }

        return ['res' => 'Arquivo adicionado com Sucesso!!'];
    }

    // Lista os anos distintos com documentos para um estabelecimento.
    public function listarAnosDisponiveis(Request $request): array
    {
        $id   = $request->input('id');
        $tipo = $request->tipo;

        if ($tipo === TipoEstabelecimentoEnum::CPF->value) {
            if ($id) {
                return $this->documentosCpfRepo->anosDistinct($id)->toArray();
            }
            throw new \RuntimeException('Erro ao Buscar Dados!!');
        }

        if ($id) {
            return $this->documentosCnpjRepo->anosDistinct($id)->toArray();
        }
        throw new \RuntimeException('Erro ao Buscar Dados!!');
    }

    // Altera o status de documentos em lote para CNPJ ou CPF.
    public function alterarStatusDocumentos(Request $request): array
    {
        $dados = $request->dados;
        $tipo  = $request->tipo;

        if ($tipo === TipoEstabelecimentoEnum::CPF->value) {
            $this->documentosCpfRepo->atualizarStatusEmLote($dados);
            return ["Menssagem" => "Alterado com Sucesso!! $tipo"];
        }

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            $this->documentosCnpjRepo->atualizarStatusEmLote($dados);
            return ["Menssagem" => "Alterado com Sucesso!!"];
        }

        throw new \RuntimeException("Tipo não encontrado!!" . $tipo);
    }

    // Adiciona um novo documento avulso para um estabelecimento específico no ano informado.
    public function adicionarDocumento(array $dados): array
    {
        $estabelecimentoId = (int) $dados['estabelecimento_id'];
        $tipo              = $dados['tipo'];
        $ano               = (int) $dados['ano'];
        $nomeDoc           = trim($dados['nome_doc']);
        $docFixo           = !empty($dados['doc_fixo']) ? 1 : 0;

        if ($tipo === TipoEstabelecimentoEnum::CPF->value) {
            if ($this->documentosCpfRepo->existeDocumentoPorNome($estabelecimentoId, $ano, $nomeDoc)) {
                throw new \InvalidArgumentException('Já existe um documento com este nome para este estabelecimento no ano selecionado.');
            }

            $doc = $this->documentosCpfRepo->criar([
                'estabelecimento_id' => $estabelecimentoId,
                'ano'                => $ano,
                'nome_doc'           => $nomeDoc,
                'doc_local'          => null,
                'doc_fixo'           => $docFixo,
                'data_doc'           => null,
                'url'                => null,
                'status'             => 0,
            ]);

            return ['mensagem' => 'Documento adicionado com sucesso!', 'doc' => $doc];
        }

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            if ($this->documentosCnpjRepo->existeDocumentoPorNome($estabelecimentoId, $ano, $nomeDoc)) {
                throw new \InvalidArgumentException('Já existe um documento com este nome para este estabelecimento no ano selecionado.');
            }

            $doc = $this->documentosCnpjRepo->criar([
                'estabelecimento_id' => $estabelecimentoId,
                'ano'                => $ano,
                'nome_doc'           => $nomeDoc,
                'doc_local'          => null,
                'doc_fixo'           => $docFixo,
                'data_doc'           => null,
                'url'                => null,
                'status'             => 0,
            ]);

            return ['mensagem' => 'Documento adicionado com sucesso!', 'doc' => $doc];
        }

        throw new \InvalidArgumentException("Tipo de estabelecimento inválido: $tipo");
    }

    // Remove o arquivo anexado de um documento (limpa referências no banco e apaga arquivo físico).
    public function apagarDocumento(Request $request): array
    {
        $id      = $request->id;
        $caminho = $request->url;
        $tipo    = $request->tipo;

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            $this->documentosCnpjRepo->atualizarPorIdDocumento($id, [
                'doc_local' => null,
                'data_doc'  => null,
                'url'       => null,
            ]);
        } else {
            $this->documentosCpfRepo->atualizarPorIdDocumento($id, [
                'doc_local' => null,
                'data_doc'  => null,
                'url'       => null,
            ]);
        }

        if (!Storage::disk('public')->exists($caminho)) {
            $fallbackSalvos = 'documentos/docs_salvos/' . basename($caminho);
            if (Storage::disk('public')->exists($fallbackSalvos)) {
                $caminho = $fallbackSalvos;
            }
        }

        $docApagado = Storage::disk('public')->delete($caminho);

        return [
            'Menssagem' => 'Documento apagado com Sucessso!!',
            'Doc'       => $docApagado,
            'Caminho'   => $caminho,
        ];
    }

    // Retorna o caminho absoluto do arquivo para download.
    public function downloadArquivo(Request $request): string
    {
        $docUrl  = $request->url;
        $caminho = storage_path("app/public/" . $docUrl);

        if (!file_exists($caminho)) {
            $fallbackSalvos = storage_path("app/public/documentos/docs_salvos/" . basename($docUrl));
            $fallbackDoc    = storage_path("app/public/documentos/" . basename($docUrl));
            if (file_exists($fallbackSalvos)) {
                $caminho = $fallbackSalvos;
            } elseif (file_exists($fallbackDoc)) {
                $caminho = $fallbackDoc;
            } else {
                throw new \RuntimeException('Arquivo não encontrado');
            }
        }

        return $caminho;
    }

    // Lista licenças do ano atual agrupadas por divisão técnica (DCQA, DCSEP, DCDM, DCSHT).
    public function gerarLicencas(): array
    {
        $ano   = date('Y');
        $dados = $this->licencaRepo->listarLicencasDoAno($ano);

        $dcqa  = $dados->filter(fn($item) => $item->divisao_tecnica === 'DCQA'  || ($item->divisao_tecnica_cpf ?? null) === 'DCQA')->values();
        $dcsep = $dados->filter(fn($item) => $item->divisao_tecnica === 'DCSEP' || ($item->divisao_tecnica_cpf ?? null) === 'DCSEP')->values();
        $dcdm  = $dados->filter(fn($item) => $item->divisao_tecnica === 'DCDM'  || ($item->divisao_tecnica_cpf ?? null) === 'DCDM')->values();
        $dcsht = $dados->filter(fn($item) => $item->divisao_tecnica === 'DCSHT' || ($item->divisao_tecnica_cpf ?? null) === 'DCSHT')->values();

        return [
            'DCQA'  => $dcqa,
            'DCSEP' => $dcsep,
            'DCDM'  => $dcdm,
            'DCSHT' => $dcsht,
        ];
    }

    // Cria documentos para um novo ano replicando docs fixos do ano anterior para CNPJ ou CPF.
    public function criarDocumentosNovoAno(Request $request): array
    {
        $id           = $request->id;
        $ano          = $request->ano;
        $id_categoria = $request->id_categoria;
        $tipo         = $request->tipo;

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            return $this->criarDocsNovoAnoCnpj($id, $ano, $id_categoria);
        }

        return $this->criarDocsNovoAnoCpf($id, $ano, $id_categoria);
    }

    // Cria documentos para um novo ano de estabelecimento CNPJ.
    private function criarDocsNovoAnoCnpj(int $id, int $ano, ?int $id_categoria): array
    {
        if ($id_categoria === null) {
            $id_categoria = \App\Models\Estabelecimentos_cnpj::findOrFail($id)->categoria_id;
        }

        if ($this->documentosCnpjRepo->existeDocumentoNoAno($id, $ano)) {
            return ['res' => 'O Estabelecimento já possui Documentos no ANO adicionado!'];
        }

        $maoirAno     = $this->documentosCnpjRepo->maxAno($id);
        $doc_categoria = $this->documentosCnpjRepo->docsPorCategoria($id_categoria);

        foreach ($doc_categoria as $d) {
            if ($d->fixo == 1) {
                $infoDoc = $this->documentosCnpjRepo->buscarDocumentoFixo($id, $maoirAno, $d->nome_doc);
                $this->documentosCnpjRepo->criar([
                    'estabelecimento_id' => $id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => $infoDoc?->doc_local,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => $infoDoc?->data_doc,
                ]);
            } else {
                $this->documentosCnpjRepo->criar([
                    'estabelecimento_id' => $id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => null,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => null,
                ]);
            }
        }

        return ['res' => "Documentos do Ano de $ano criados!"];
    }

    // Cria documentos para um novo ano de estabelecimento CPF.
    private function criarDocsNovoAnoCpf(int $id, int $ano, ?int $id_categoria): array
    {
        if ($id_categoria === null) {
            $id_categoria = \App\Models\Estabelecimentos_cpf::findOrFail($id)->categoria_id;
        }

        if ($this->documentosCpfRepo->existeDocumentoNoAno($id, $ano)) {
            return ['res' => 'O Estabelecimento já possui Documentos no ANO adicionado!'];
        }

        $maoirAno     = $this->documentosCpfRepo->maxAno($id);
        $doc_categoria = $this->documentosCpfRepo->docsPorCategoria($id_categoria);

        foreach ($doc_categoria as $d) {
            if ($d->fixo == 1) {
                $infoDoc = $this->documentosCpfRepo->buscarDocumentoFixo($id, $maoirAno, $d->nome_doc);
                $this->documentosCpfRepo->criar([
                    'estabelecimento_id' => $id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => $infoDoc?->doc_local,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => $infoDoc?->data_doc,
                ]);
            } else {
                $this->documentosCpfRepo->criar([
                    'estabelecimento_id' => $id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => null,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => null,
                ]);
            }
        }

        return ['res' => "Documentos do Ano de $ano criados!"];
    }

    // Gera um arquivo de protocolo Word (.docx) para CNPJ ou CPF e retorna o caminho do arquivo.
    public function gerarProtocolo(Request $request, string $id): string
    {
        $n          = $request->n;
        $especificos = $request->especificos;
        $tipo       = $request->tipo;
        $validadeParts = explode('-', $request->validade);

        if ($tipo === TipoEstabelecimentoEnum::CNPJ->value) {
            return $this->gerarProtocoloCnpj($id, $n, $especificos, $validadeParts);
        }

        return $this->gerarProtocoloCpf($id, $n, $especificos, $validadeParts);
    }

    // Gera protocolo Word para CNPJ.
    private function gerarProtocoloCnpj(string $id, $n, $especificos, array $validadeParts): string
    {
        $dados = \App\Models\Estabelecimentos_cnpj::select('nome_responsavel', 'endereco', 'numero_endereco', 'localidade', 'cnpj', 'atividade_principal')
            ->findOrFail($id);

        $ano     = date('Y');
        $mes     = date('m');
        $dia     = date('d');
        $nomeDoc = $id . "protocolo.docx";

        $template = new TemplateProcessor(storage_path('app/public/templates/templete_protocolo.docx'));

        foreach ($especificos as $valor) {
            $template->setValue($valor, 'X');
        }

        $template->setValue('nomeResponsavel', $dados->nome_responsavel);
        $template->setValue('endereco', $dados->endereco);
        $template->setValue('numero', $dados->numero_endereco);
        $template->setValue('localidade', $dados->localidade);
        $template->setValue('cnpj', FormatadorHelper::formatCNPJ($dados->cnpj));
        $template->setValue('atividadePrincipal', $dados->atividade_principal);
        $template->setValue('ano', $ano);
        $template->setValue('mes', $mes);
        $template->setValue('dia', $dia);
        $template->setValue('av', $validadeParts[0]);
        $template->setValue('mv', $validadeParts[1]);
        $template->setValue('dv', $validadeParts[2]);
        $template->setValue('n', $n);

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Substitui variáveis não mapeadas por espaços não quebráveis para remover placeholders.
            $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
        }

        $dirDocsTemp = storage_path('app/public/documentos/docs_temp');
        if (!is_dir($dirDocsTemp)) {
            mkdir($dirDocsTemp, 0755, true);
        }
        $caminho = $dirDocsTemp . DIRECTORY_SEPARATOR . $nomeDoc;
        $template->saveAs($caminho);

        return $caminho;
    }

    // Gera protocolo Word para CPF.
    private function gerarProtocoloCpf(string $id, $n, $especificos, array $validadeParts): string
    {
        $dados = \App\Models\Estabelecimentos_cpf::select('nome', 'endereco', 'numero_endereco', 'localidade', 'atividade_principal')
            ->findOrFail($id);

        $ano     = date('Y');
        $mes     = date('m');
        $dia     = date('d');
        $nomeDoc = $id . "protocolo.docx";

        $template = new TemplateProcessor(storage_path('app/public/templates/templete_protocolo.docx'));

        foreach ($especificos as $valor) {
            $template->setValue($valor, 'X');
        }

        $template->setValue('nomeResponsavel', $dados->nome);
        $template->setValue('endereco', $dados->endereco);
        $template->setValue('numero', $dados->numero_endereco);
        $template->setValue('localidade', $dados->localidade);
        $template->setValue('cnpj', 'XXXXXXXXXXXXX');
        $template->setValue('atividadePrincipal', $dados->atividade_principal);
        $template->setValue('ano', $ano);
        $template->setValue('mes', $mes);
        $template->setValue('dia', $dia);
        $template->setValue('av', $validadeParts[0]);
        $template->setValue('mv', $validadeParts[1]);
        $template->setValue('dv', $validadeParts[2]);
        $template->setValue('n', $n);

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Substitui variáveis não mapeadas por espaços não quebráveis para remover placeholders.
            $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
        }

        $dirDocsTemp = storage_path('app/public/documentos/docs_temp');
        if (!is_dir($dirDocsTemp)) {
            mkdir($dirDocsTemp, 0755, true);
        }
        $caminho = $dirDocsTemp . DIRECTORY_SEPARATOR . $nomeDoc;
        $template->saveAs($caminho);

        return $caminho;
    }

    // Cria ou finaliza uma intimação/constatação com base no flag 'finalizar'.
    public function salvarIntimacaoConstatacao(Request $request): array
    {
        $id                  = $request->id;
        $estabelecimento_id  = $request->estabelecimento_id;
        $ano                 = $request->ano;
        $tipo                = $request->tipo;
        $tipoEstabelecimento = $request->tipoEstabelecimento;
        $descricao           = $request->descricao;
        $dataExpiracao       = $request->data_expiracao;
        $finalizar           = $request->finalizar;

        if ($finalizar == 1) {
            $this->intimacaoRepo->finalizar($tipoEstabelecimento, $id);
            return ['Menssagem' => "Finalizado com sucesso!"];
        }

        $this->intimacaoRepo->criarOuAtualizar($tipoEstabelecimento, [
            'estabelecimento_id' => $estabelecimento_id,
            'ano'                => $ano,
            'tipo'               => $tipo,
            'descricao'          => $descricao,
            'data_expiracao'     => $dataExpiracao,
        ]);

        return ['Menssagem' => "Criado com sucesso!"];
    }
}