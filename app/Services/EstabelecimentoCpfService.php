<?php

namespace App\Services;

use App\Helpers\FormatadorHelper;
use App\Models\Controle_licencas;
use App\Models\Documentos_cpf;
use App\Models\Estabelecimentos_cpf;
use App\Repositories\EstabelecimentoCpfRepository;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class EstabelecimentoCpfService
{
    private EstabelecimentoCpfRepository $repository;

    public function __construct(EstabelecimentoCpfRepository $repository)
    {
        $this->repository = $repository;
    }

    // Lista todos os estabelecimentos CPF resolvendo a situação (ativas, vencidas, notificadas).
    public function listarTodosEsbtCpf(): Collection
    {
        return $this->repository->listarEstbCpf()->map(fn($item) => $this->resolverSituacao($item));
    }

    // Ajusta a situação do estabelecimento conforme validade da licença e status de intimação.
    private function resolverSituacao(object $item): object
    {
        if ($item->situacao != 3 && $item->ano == $item->ano_doc) {
            if (Carbon::parse($item->validade)->lessThanOrEqualTo(now())) {
                $this->repository->atualizarSituacaoCpf($item->id, 3);
                $item->situacao = 3;
            } elseif ($item->situacao == 1 && $item->status == 1) {
                $this->repository->atualizarSituacaoCpf($item->id, 4);
                $item->situacao = 4;
            }
        }
        return $item;
    }

    // Cadastra um novo estabelecimento CPF e cria seus documentos iniciais dentro de uma transação.
    public function criarEstabelecimentoComDocs(array $dados)
    {
        $categoria = $dados['categoria_id'];
        $dados['situacao'] = 0;

        return DB::transaction(function () use ($categoria, $dados) {
            $estabelecimento = Estabelecimentos_cpf::create($dados);
            $docs = $this->repository->docsPorCategoria($categoria);
            $ano = now()->year;

            foreach ($docs as $d) {
                Documentos_cpf::create([
                    'estabelecimento_id' => $estabelecimento->id,
                    'ano'                => $ano,
                    'nome_doc'           => $d->nome_doc,
                    'doc_local'          => null,
                    'doc_fixo'           => $d->fixo,
                    'data_doc'           => null,
                ]);
            }

            return $estabelecimento->load('documentos');
        });
    }

    // Gera (ou reutiliza) a licença de funcionamento em Word para CPF e retorna o caminho do arquivo.
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
        $bairro             = $estabelecimento->bairro;
        $divisaoTecnica     = $estabelecimento->divisao_tecnica;
        $atividadePrincipal = $estabelecimento->atividade_principal;
        $nomeDoc            = $id . "licenca_cpf.docx";
        $ano                = date('Y');
        $mes                = date('m');
        $dia                = date('d');
        $mesv               = 0;
        $anoV               = 0;
        $numeroLicenca      = 0;

        $licenca = $this->repository->buscarLicencaPorAno_e_Id($id);

        if ($licenca) {
            $numeroLicenca = $licenca->numero_licenca;
            if ($licenca->ano == $ano_doc) {
                $numeroLicenca = $licenca->numero_licenca;
                $anoV = (new DateTime($licenca->validade))->format('Y');
            } elseif ($ano_doc > $licenca->ano) {
                $mesv = $categoria == "Drogaria e Ervanaria" ? 04 : 03;
                $anoV = $ano_doc + 1;
                $data = Carbon::create($anoV, $mesv, 30);

                $ultimaLicenca = $this->repository->buscarUltimaLicenca($ano_doc, $divisaoTecnica);
                $numeroLicenca = $ultimaLicenca + 1;

                Controle_licencas::where('estabelecimento_id_cpf', $id)->update([
                    'ano'           => $ano_doc,
                    'numero_licenca' => $numeroLicenca,
                    'validade'      => $data,
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
                'estabelecimento_id_cpf' => $id,
                'ano'                   => $ano_doc,
                'numero_licenca'         => $numeroLicenca,
                'validade'              => $data,
            ]);
        }

        $template = new TemplateProcessor(storage_path('app/public/templates/templete_licenca_cpf.docx'));

        if ($tipo == "licenca") {
            $template->setValue('r', "\u{00A0}\u{00A0}\u{00A0}");
            $template->setValue('l', 'X');
        } elseif ($tipo == "renovacao") {
            $template->setValue('r', 'X');
            $template->setValue('l', "\u{00A0}\u{00A0}\u{00A0}");
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', mb_strtoupper($nomeEstabelecimento, 'UTF-8'));
        $template->setValue('numero', $numero);
        $template->setValue('endereco', mb_strtoupper($endereco, 'UTF-8'));
        $template->setValue('bairro', mb_strtoupper($bairro, 'UTF-8'));
        $template->setValue('atividadePrincipal', mb_strtoupper($atividadePrincipal, 'UTF-8'));
        $template->setValue('numeroLicenca', $numeroLicenca);
        $template->setValue('categoriaLicenca', mb_strtoupper($divisaoTecnica, 'UTF-8'));
        $template->setValue('anoV', $anoV);
        $template->setValue('mesV', $categoria == "Drogaria e Ervanaria" ? "ABRIL" : "MARÇO");

        $template->saveAs(storage_path('app/public/documentos/' . $nomeDoc));

        return storage_path('app/public/documentos/' . $nomeDoc);
    }

    // Gera um requerimento em Word para CPF com base nos itens selecionados e categoria.
    public function gerarRequerimento(array $dadosInputs, int $id, string $categoria): string
    {
        $dados               = Estabelecimentos_cpf::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco            = $dados->endereco;
        $numeroEndereco      = $dados->numero_endereco;
        $localidade          = $dados->localidade;
        $atividadePrincipal  = $dados->atividade_principal;
        $cpf                 = $dados->cpf;
        $nomeDoc             = $nomeEstabelecimento . "_requerimento_cpf.docx";

        if ($categoria == "Profissional Liberal") {
            $titulo_profissional = $dados->formacao_profissional;
            $registro_conselho   = $dados->registro_conselho;

            $template = new TemplateProcessor(storage_path('app/public/templates/templete_requerimento_doc_cpf_pl_of.docx'));

            foreach ($dadosInputs as $valor) {
                $template->setValue($valor, 'X');
            }

            $template->setValue('dia', date('d'));
            $template->setValue('mes', date('m'));
            $template->setValue('ano', date('Y'));
            $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
            $template->setValue('atividadePrincipal', $atividadePrincipal);
            $template->setValue('numero', $numeroEndereco);
            $template->setValue('endereco', $endereco);
            $template->setValue('localidade', $localidade);
            $template->setValue('cpf', FormatadorHelper::formatarCpf($cpf));
            $template->setValue('titulo_profissional', $titulo_profissional);
            $template->setValue('registro_conselho', $registro_conselho);

            $variables = $template->getVariables();
            foreach ($variables as $variable) {
                // Substitui variáveis não mapeadas por espaços não quebráveis para remover placeholders.
                $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
            }
        } elseif ($categoria == "Autônomo") {
            $template = new TemplateProcessor(storage_path('app/public/templates/templete_requerimento_doc_cpf_au_of.docx'));

            foreach ($dadosInputs as $valor) {
                $template->setValue($valor, 'X');
            }

            $template->setValue('dia', date('d'));
            $template->setValue('mes', date('m'));
            $template->setValue('ano', date('Y'));
            $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
            $template->setValue('atividadePrincipal', $atividadePrincipal);
            $template->setValue('numero', $numeroEndereco);
            $template->setValue('endereco', $endereco);
            $template->setValue('localidade', $localidade);
            $template->setValue('cpf', FormatadorHelper::formatarCpf($cpf));

            $variables = $template->getVariables();
            foreach ($variables as $variable) {
                // Substitui variáveis não mapeadas por string vazia para remover placeholders.
                $template->setValue($variable, '');
            }
        }

        $dirDocumentos = storage_path('app/public/documentos');
        if (!is_dir($dirDocumentos)) {
            mkdir($dirDocumentos, 0755, true);
        }
        $caminho = $dirDocumentos . DIRECTORY_SEPARATOR . $nomeDoc;
        $template->saveAs($caminho);

        return $caminho;
    }
}