<?php

namespace App\Repositories;

use App\Models\Categorias;
use App\Models\Controle_licencas;
use App\Models\Doc_categoria;
use App\Models\Estabelecimentos_cnpj;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstabelecimentoCnpjRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function listarEstbCnpj()
    {
        return DB::table('estabelecimentos_cnpj')
            ->leftJoin('controle_licencas', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'controle_licencas.estabelecimento_id_cnpj')
                ->whereRaw('controle_licencas.id = (
                SELECT id FROM controle_licencas 
                WHERE estabelecimento_id_cnpj = estabelecimentos_cnpj.id 
                ORDER BY ano DESC LIMIT 1
                )');
            })
            ->leftJoin('documentos_cnpj', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'documentos_cnpj.estabelecimento_id')
                ->whereRaw('documentos_cnpj.id_documento = (
                SELECT id_documento FROM documentos_cnpj 
                WHERE estabelecimento_id = estabelecimentos_cnpj.id 
                ORDER BY ano DESC LIMIT 1
             )');
            })
            ->leftJoin('categorias', 'estabelecimentos_cnpj.categoria_id', '=', 'categorias.id_categoria')
            ->leftJoin('intimacao_constatacao', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'intimacao_constatacao.estabelecimento_id')
                ->whereRaw('intimacao_constatacao.id_intimacao_constatacao = (
                SELECT id_intimacao_constatacao FROM intimacao_constatacao
                WHERE estabelecimento_id = estabelecimentos_cnpj.id 
                AND tipo = 1
                ORDER BY ano DESC LIMIT 1
                )');            
            })
        ->select('estabelecimentos_cnpj.id', 'estabelecimentos_cnpj.cnpj', 'estabelecimentos_cnpj.nome_fantasia', 'estabelecimentos_cnpj.tipo_estabelecimento','estabelecimentos_cnpj.categoria_id', 'estabelecimentos_cnpj.situacao', 'controle_licencas.ano', 'controle_licencas.validade', 'documentos_cnpj.ano as ano_doc', 'categorias.nome_categoria', 'intimacao_constatacao.status')
        ->get();
    }

    public function atualizarSituacaoCnpj(int $id, int $situacao): void 
    {
        Estabelecimentos_cnpj::where('id', $id)->update(['situacao' => $situacao]);
    }

    public function docsPorCategoria(int $categoria_id): Collection
    {
        return Doc_categoria::where('categoria_id', $categoria_id)->get(['nome_doc', 'fixo']);
    }

    public function buscarDadosParaLicenca(int $id){
        return Estabelecimentos_cnpj::select('nome_fantasia', 'endereco', 'numero_endereco', 'razao_social','bairro', 'cnpj', 'divisao_tecnica', 'atividade_principal')
         ->findOrFail($id);
    }

    public function buscarUltimaLicenca(int $ano_doc){
        return Controle_licencas::join('estabelecimentos_cnpj', 'estabelecimentos_cnpj.id', '=', 'controle_licencas.estabelecimento_id_cnpj')
                        ->where('divisao_tecnica', 'estabelecimentos_cnpj.divisao_tecnica')
                        ->where('ano', $ano_doc)
                        ->lockForUpdate()
                        ->max('numero_licenca') ?? 0;
    }

    public function buscarLicencaPorAno_e_Id(int $id, int $ano){
      return Controle_licencas::where('estabelecimento_id_cnpj', $id)
                ->where('ano', $ano)
                ->first();
    }

   

}
