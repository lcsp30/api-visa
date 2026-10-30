<?php

namespace App\Services;

use App\Helpers\FormatadorHelper;
use App\Models\Controle_licencas;
use App\Models\Documentos_cnpj;
use App\Models\Estabelecimentos_cnpj;
use App\Repositories\EstabelecimentoCnpjRepository;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class EstabelecimentoCnpjService
{
    private EstabelecimentoCnpjRepository $repository;

    public function __construct(EstabelecimentoCnpjRepository $repository)
    {
        $this->repository = $repository;
    }

    // Lista estabelecimentos CNPJ paginados (70/página) resolvendo a situação de cada item da página.
    public function listarTodosEsbtCnpj(string $busca = ''): LengthAwarePaginator
    {
        return $this->repository->listarEstbCnpj($busca)
            ->paginate(70)
            ->through(fn($item) => $this->resolverSituacao($item));
    }

    // Ajusta a situação do estabelecimento conforme validade da licença e status de intimação.
    private function resolverSituacao(object $item): object
    {
        if ($item->situacao != 3 && $item->ano == $item->ano_doc) {
            if (Carbon::parse($item->validade)->lessThanOrEqualTo(now())) {
                $this->repository->atualizarSituacaoCnpj($item->id, 3);
                $item->situacao = 3;
            } elseif ($item->situacao == 1 && $item->status == 1) {
                $this->repository->atualizarSituacaoCnpj($item->id, 4);
                $item->situacao = 4;
            }
        }

        return $item;
    }

    // Cadastra um novo estabelecimento CNPJ e cria seus documentos iniciais dentro de uma transação.
    public function criarEstabelecimentoComDocs(array $dados)
    {
        $categoria = $dados['categoria_id'];
        $dados['situacao'] = 0;

        return DB::transaction(function () use ($categoria, $dados) {
            $estabelecimento = Estabelecimentos_cnpj::create($dados);
            $docs = $this->repository->docsPorCategoria($categoria);
            $ano = now()->year;

            foreach ($docs as $d) {
                Documentos_cnpj::create([
                    'estabelecimento_id' => $estabelecimento->id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => null,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => null,
                ]);
            }

            return $estabelecimento->load('documento');
        });
    }

    // Gera (ou reutiliza) a licença de funcionamento em Word e retorna o caminho do arquivo.
    public function baixarLicenca(array $dados): string
    {
        $id        = $dados['id'];
        $tipo      = $dados['tipo'];
        $categoria = $dados['categoria'];
        $ano_doc   = $dados['ano'];

        $estabelecimento    = $this->repository->buscarDadosParaLicenca($id);
        $nomeEstabelecimento = $estabelecimento->nome_fantasia;
        $endereco           = $estabelecimento->endereco;
        $numero             = $estabelecimento->numero_endereco;
        $razaoSocial        = $estabelecimento->razao_social;
        $bairro             = $estabelecimento->bairro;
        $cnpj               = $estabelecimento->cnpj;
        $divisaoTecnica     = $estabelecimento->divisao_tecnica;
        $atividadePrincipal = $estabelecimento->atividade_principal;
        $nomeDoc            = $id . "licenca_cnpj.docx";
        $ano                = date('Y');
        $mes                = date('m');
        $dia                = date('d');
        $mesv               = 0;
        $anoV               = 0;
        $numeroLicenca      = 0;

        $licenca = $this->repository->buscarLicencaId($id);

        if ($licenca) {
            if ($licenca->ano == $ano_doc) {
                $numeroLicenca = $licenca->numero_licenca;
                $anoV = (new DateTime($licenca->validade))->format('Y');
            } elseif ($ano_doc > $licenca->ano) {
                $mesv = $categoria == "Drogaria e Ervanaria" ? 04 : 03;
                $anoV = $ano_doc + 1;
                $data = Carbon::create($anoV, $mesv, 30);

                $ultimaLicenca = $this->repository->buscarUltimaLicenca($ano_doc, $divisaoTecnica);
                $numeroLicenca = $ultimaLicenca + 1;

                Controle_licencas::where('estabelecimento_id_cnpj', $id)->update([
                    'ano'             => $ano_doc,
                    'numero_licenca'  => $numeroLicenca,
                    'validade'        => $data,
                    'divisao_tecnica' => $divisaoTecnica,
                ]);
            } else {
                throw new \Exception('Não é possível gerar Licença de Anos Anteriores!');
            }
        } else {
            $mesv = $categoria == "Drogaria e Ervanaria" ? 04 : 03;
            $anoV = $ano_doc + 1;
            $data = Carbon::create($anoV, $mesv, 30);

            $ultimaLicenca = $this->repository->buscarUltimaLicenca($ano_doc, $divisaoTecnica);
            $numeroLicenca = $ultimaLicenca + 1;

            Controle_licencas::create([
                'estabelecimento_id_cnpj' => $id,
                'ano'                    => $ano_doc,
                'numero_licenca'          => $numeroLicenca,
                'validade'               => $data,
                'divisao_tecnica'        => $divisaoTecnica,
            ]);
        }

        $template = new TemplateProcessor(storage_path('app/public/templates/templete_licenca.docx'));

        if ($tipo == "licenca") {
            $template->setValue('r', "\u{00A0}\u{00A0}\u{00A0}");
            $template->setValue('l', ' X ');
        } elseif ($tipo == "renovacao") {
            $template->setValue('r', ' X ');
            $template->setValue('l', "\u{00A0}\u{00A0}\u{00A0}");
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', mb_strtoupper((string) $nomeEstabelecimento, 'UTF-8'));
        $template->setValue('atividadePrincipal', mb_strtoupper((string) $atividadePrincipal, 'UTF-8'));
        $template->setValue('razaoSocial', mb_strtoupper((string) $razaoSocial, 'UTF-8'));
        $template->setValue('endereco', mb_strtoupper((string) $endereco, 'UTF-8'));
        $template->setValue('numero', (string) $numero);
        $template->setValue('numeroLicenca', $numeroLicenca);
        $template->setValue('categoriaLicenca', mb_strtoupper((string) $divisaoTecnica, 'UTF-8'));
        $template->setValue('bairro', mb_strtoupper((string) $bairro, 'UTF-8'));
        $template->setValue('cnpj', FormatadorHelper::formatCNPJ((string) $cnpj));
        $template->setValue('anoV', $anoV);
        $template->setValue('mesV', $categoria == "Drogaria e Ervanaria" ? "ABRIL" : "MARÇO ");

        $dirDocsTemp = storage_path('app/public/documentos/docs_temp');
        if (!is_dir($dirDocsTemp)) {
            mkdir($dirDocsTemp, 0755, true);
        }

        $caminhoArquivo = $dirDocsTemp . DIRECTORY_SEPARATOR . $nomeDoc;
        $template->saveAs($caminhoArquivo);

        return $caminhoArquivo;
    }

    // Gera um requerimento em Word para CNPJ com base nos itens selecionados.
    public function gerarRequerimento(array $dadosInputs, int $id): string
    {
        $dados               = Estabelecimentos_cnpj::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco            = $dados->endereco;
        $numeroEndereco      = $dados->numero_endereco;
        $razaoSocial         = $dados->razao_social;
        $localidade          = $dados->localidade;
        $atividadePrincipal  = $dados->atividade_principal;
        $cpf                 = $dados->cpf;
        $cnpj                = $dados->cnpj;
        $nomeDoc             = $id . "_requerimento_cnpj.docx";

        $template = new TemplateProcessor(storage_path('app/public/templates/templete_requerimento_doc_cnpj_of.docx'));

        foreach ($dadosInputs as $valor) {
            $template->setValue($valor, 'X');
        }

        $template->setValue('dia', date('d'));
        $template->setValue('mes', date('m'));
        $template->setValue('ano', date('Y'));
        $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
        $template->setValue('razaoSocial', $razaoSocial);
        $template->setValue('endereco', $endereco);
        $template->setValue('atividadePrincipal', $atividadePrincipal);
        $template->setValue('numeroEndereco', $numeroEndereco);
        $template->setValue('localidade', $localidade);
        $template->setValue('cpf', FormatadorHelper::formatarCpf($cpf));
        $template->setValue('cnpj', FormatadorHelper::formatCNPJ($cnpj));

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Substitui variáveis não mapeadas por espaços não quebráveis para remover placeholders.
            $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
        }

        $dirDocsTemp = storage_path('app/public/documentos/docs_temp');
        if (!is_dir($dirDocsTemp)) {
            mkdir($dirDocsTemp, 0755, true);
        }

        $caminhoDocx = $dirDocsTemp . DIRECTORY_SEPARATOR . $nomeDoc;
        $template->saveAs($caminhoDocx);

        return $caminhoDocx;
    }
}