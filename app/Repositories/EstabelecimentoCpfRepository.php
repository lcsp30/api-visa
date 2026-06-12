<?php

namespace App\Repositories;

use App\Models\Categorias_cpf;
use App\Models\Controle_licencas;
use App\Models\Doc_categoria_cpf;
use App\Models\Estabelecimentos_cpf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstabelecimentoCpfRepository
{
    public function __construct()
    {
        //
    }

    // Lista todos os estabelecimentos CPF com licença, documento e intimação mais recentes via joins.
    public function listarEstbCpf()
    {
        return DB::table('estabelecimentos_cpf')
            ->leftJoin('controle_licencas', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'controle_licencas.estabelecimento_id_cpf')
                ->whereRaw('controle_licencas.id = (
                SELECT id FROM controle_licencas 
                WHERE estabelecimento_id_cpf = estabelecimentos_cpf.id 
                ORDER BY ano DESC LIMIT 1
                )');
            })
            ->leftJoin('documentos_cpf', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'documentos_cpf.estabelecimento_id')
                ->whereRaw('documentos_cpf.id_documento = (
                SELECT id_documento FROM documentos_cpf 
                WHERE estabelecimento_id = estabelecimentos_cpf.id 
                ORDER BY ano DESC LIMIT 1
             )');
            })
            ->leftJoin('categorias_cpf', 'estabelecimentos_cpf.categoria_id', '=', 'categorias_cpf.id_categoria')
            ->leftJoin('intimacao_constatacao_cpf', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'intimacao_constatacao_cpf.estabelecimento_id')
                ->whereRaw('intimacao_constatacao_cpf.id_intimacao_constatacao = (
                SELECT id_intimacao_constatacao FROM intimacao_constatacao_cpf
                WHERE estabelecimento_id = estabelecimentos_cpf.id 
                AND tipo = 1
                ORDER BY ano DESC LIMIT 1
                )');            
            })
        ->select('estabelecimentos_cpf.id', 'estabelecimentos_cpf.cpf', 'estabelecimentos_cpf.nome', 'estabelecimentos_cpf.nome_fantasia', 'estabelecimentos_cpf.tipo_estabelecimento','estabelecimentos_cpf.categoria_id', 'estabelecimentos_cpf.situacao', 'controle_licencas.ano', 'controle_licencas.validade', 'documentos_cpf.ano as ano_doc', 'categorias_cpf.nome_categoria', 'intimacao_constatacao_cpf.status')
        ->get();
    }

    // Atualiza a situação (status) de um estabelecimento CPF.
    public function atualizarSituacaoCpf(int $id, int $situacao): void 
    {
        Estabelecimentos_cpf::where('id', $id)->update(['situacao' => $situacao]);
    }

    // Retorna os tipos de documentos vinculados a uma categoria de CPF.
    public function docsPorCategoria(int $categoria_id): Collection
    {
        return Doc_categoria_cpf::where('categoria_id', $categoria_id)->get(['nome_doc', 'fixo']);
    }

    // Busca dados essenciais de um estabelecimento CPF para geração da licença.
    public function buscarDadosParaLicenca(int $id)
    {
        return Estabelecimentos_cpf::select('nome_fantasia', 'endereco', 'numero_endereco', 'bairro', 'divisao_tecnica', 'atividade_principal')
         ->findOrFail($id);
    }

    // Retorna o último número de licença emitido no ano para uma divisão técnica de CPF.
    public function buscarUltimaLicenca(int $ano_doc, string $divisaoTecnica)
    {
        $n = Controle_licencas::join('estabelecimentos_cpf', 'estabelecimentos_cpf.id', '=', 'controle_licencas.estabelecimento_id_cpf')
                        ->where('divisao_tecnica', $divisaoTecnica)
                        ->where('ano', $ano_doc)
                        ->max('numero_licenca') ?? 0;
            return $n;
    }

    // Busca a licença existente de um estabelecimento CPF pelo ID.
    public function buscarLicencaPorAno_e_Id(int $id)
    {
        return Controle_licencas::where('estabelecimento_id_cpf', $id)
                ->first();
    }
}