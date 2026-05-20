<?php

namespace App\Services;

use App\Models\Controle_licencas;
use App\Models\Documentos_cnpj;
use App\Models\Estabelecimentos_cnpj;
use App\Repositories\EstabelecimentoCnpjRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class EstabelecimentoCnpjService
{
    private EstabelecimentoCnpjRepository $repository;

    public function __construct(EstabelecimentoCnpjRepository $repository)
    {
        $this->repository = $repository;
    }

    public function formatCNPJ($cnpj)
    {
        // 1. Remove tudo que não é número
        $cnpj = preg_replace("/\D/", "", $cnpj);
        // 2. Aplica a máscara: 00.000.000/0000-00
        return preg_replace(
            "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/",
            "$1.$2.$3/$4-$5",
            $cnpj
        );
    }

    public function formatarCpf($cpf)
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace(
            "/(\d{3})(\d{3})(\d{3})(\d{2})/",
            "$1.$2.$3-$4",
            $cpf
        );
    }

    public function listarTodosEsbtCnpj(): Collection
    {
        return $this->repository->listarEstbCnpj()->map(fn($item) => $this->resolverSituacao($item));
    }

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
            // else: situacao != 3, ano bate, mas nenhuma condição acima → mantém o que está
        }
        // se situacao == 3 ou ano != ano_doc → não toca em nada

        return $item;
    }

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

    public function baixarLicenca(array $dados)
    {
        $id = $dados['id'];
        $tipo = $dados['tipo'];
        $categoria = $dados['categoria'];
        $ano_doc = $dados['ano'];

        $estabelecimento = $this->repository->buscarDadosParaLicenca($id);
        $nomeEstabelecimento = $estabelecimento->nome_fantasia;
        $endereco = $estabelecimento->endereco;
        $numero = $estabelecimento->numero_endereco;
        $razaoSocial = $estabelecimento->razao_social;
        $bairro = $estabelecimento->bairro;
        $cnpj = $estabelecimento->cnpj;
        $divisaoTecnica = $estabelecimento->divisao_tecnica;
        $atividadePrincipal = $estabelecimento->atividade_principal;
        $nomeDoc = $id . "licenca_cnpj.docx";
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');
        $mesv = 0;

        $licenca = $this->repository->buscarLicencaPorAno_e_Id($id, $ano_doc);

        if ($licenca) {
            $numeroLicenca = $licenca->numero_licenca;
        } else {

            if ($categoria == "Drogaria e Ervanaria") {
                $mesv = 04;
            } else {
                $mesv = 03;
            }
            $anov = $ano + 1;

            $data = Carbon::create($anov, $mesv, 30);

            $ultimaLicenca = $this->repository->buscarUltimaLicenca($ano_doc);

            Controle_licencas::create([
                'estabelecimento_id_cnpj' => $id,
                'ano' => $ano_doc,
                'numero_licenca' => $ultimaLicenca + 1,
                'validade' => $data
            ]);

            $numeroLicenca = $ultimaLicenca + 1;
        }

        $template = new TemplateProcessor(public_path('storage/templates/templete_licenca.docx'));

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
        $template->setValue('nomeEstabelecimento', mb_strtoupper($nomeEstabelecimento, 'UTF-8'));
        $template->setValue('atividadePrincipal', mb_strtoupper($atividadePrincipal, 'UTF-8'));
        $template->setValue('razaoSocial', mb_strtoupper($razaoSocial, 'UTF-8'));
        $template->setValue('endereco', mb_strtoupper($endereco, 'UTF-8'));
        $template->setValue('numero', $numero);
        $template->setValue('numeroLicenca', $numeroLicenca);
        $template->setValue('categoriaLicenca', $divisaoTecnica);
        $template->setValue('bairro', mb_strtoupper($bairro, 'UTF-8'));
        $template->setValue('cnpj', $this->formatCNPJ($cnpj));
        $template->setValue('anoV', $ano + 1);

        if ($categoria == "Drogaria e Ervanaria") {
            $template->setValue('mesV', "ABRIL");
        } else {
            $template->setValue('mesV', "MARÇO ");
        }

        $template->saveAs(__DIR__ . '/../../public/storage/documentos/' . $nomeDoc);

        $caminho = storage_path('app/public/documentos/' . $nomeDoc);

        return $caminho;
    }

    public function gerarRequerimento(array $dadosInputs, int $id)
    {
        $dados = Estabelecimentos_cnpj::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco = $dados->endereco;
        $numeroEndereco = $dados->numero_endereco;
        $razaoSocial = $dados->razao_social;
        $localidade = $dados->localidade;
        $atividadePrincipal = $dados->atividade_principal;
        $cpf = $dados->cpf;
        $cnpj = $dados->cnpj;
        $nomeDoc = $id . "requerimento_cnpj.docx";
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');

        $template = new TemplateProcessor(__DIR__ . '/../../public/storage/templates/templete_requerimento_doc_cnpj_of.docx');

        foreach ($dadosInputs as $valor) {
            $template->setValue($valor, 'X');
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
        $template->setValue('razaoSocial', $razaoSocial);
        $template->setValue('endereco', $endereco);
        $template->setValue('atividadePrincipal', $atividadePrincipal);
        $template->setValue('numeroEndereco', $numeroEndereco);
        $template->setValue('localidade', $localidade);
        $template->setValue('cpf', $this->formatarCpf($cpf));
        $template->setValue('cnpj', $this->formatCNPJ($cnpj));

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Se a variável não foi substituída, ela ainda existe.
            // Define como string vazia para remover o placeholder.
            $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
        }

        $template->saveAs(__DIR__ . '/../../public/storage/documentos/' . $nomeDoc);

        $caminho = storage_path('app/public/documentos/' . $nomeDoc);

        $soffice = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';
        $outDir = storage_path('app/public/documentos/');

        $comando = "$soffice --headless --convert-to pdf \"$caminho\" --outdir \"$outDir\"";

        exec($comando);

        $pdf = "storage/documentos/" . $id . "requerimento_cnpj.pdf";

        $link = asset($pdf);

        return $link;
    }
}
