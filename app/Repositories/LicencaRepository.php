<?php

namespace App\Repositories;

use App\Models\Controle_licencas;
use App\Enums\TipoEstabelecimentoEnum;

class LicencaRepository
{
    // Busca a última licença gerada no ano, considerando a divisão técnica, com lock para evitar concorrência.
    public function buscarUltima(string $tipoEstabelecimento, int $ano, string $divisaoTecnica): int
    {
        if ($tipoEstabelecimento === TipoEstabelecimentoEnum::CNPJ->value) {
            $ultimoNumero = Controle_licencas::join('estabelecimentos_cnpj', 'estabelecimentos_cnpj.id', '=', 'controle_licencas.estabelecimento_id_cnpj')
                ->where('estabelecimentos_cnpj.divisao_tecnica', $divisaoTecnica)
                ->where('ano', $ano)
                ->lockForUpdate()
                ->max('numero_licenca');
        } else {
            $ultimoNumero = Controle_licencas::join('estabelecimentos_cpf', 'estabelecimentos_cpf.id', '=', 'controle_licencas.estabelecimento_id_cpf')
                ->where('estabelecimentos_cpf.divisao_tecnica', $divisaoTecnica)
                ->where('ano', $ano)
                ->lockForUpdate()
                ->max('numero_licenca');
        }

        return $ultimoNumero ?? 0;
    }

    // Busca uma licença por ano e ID do estabelecimento, conforme tipo (CNPJ/CPF).
    public function buscarPorAnoEId(string $tipoEstabelecimento, int $id, int $ano): ?Controle_licencas
    {
        $query = Controle_licencas::where('ano', $ano);

        if ($tipoEstabelecimento === TipoEstabelecimentoEnum::CNPJ->value){
            $query->where('estabelecimento_id_cnpj', $id);
        } else {
            $query->where('estabelecimento_id_cpf', $id);
        }
        
        return $query->first();
    }

    // Cria uma nova licença no controle de licenças.
    public function criar(array $dados): Controle_licencas
    {
        return Controle_licencas::create($dados);
    }

    // Lista licenças do ano informado com joins para estabelecimentos e categorias.
    public function listarLicencasDoAno(int $ano)
    {
        return \Illuminate\Support\Facades\DB::table('controle_licencas')
            ->where('ano', $ano)
            ->leftJoin('estabelecimentos_cnpj', 'controle_licencas.estabelecimento_id_cnpj', '=', 'estabelecimentos_cnpj.id')
            ->leftJoin('estabelecimentos_cpf', 'controle_licencas.estabelecimento_id_cpf', '=', 'estabelecimentos_cpf.id')
            ->leftJoin('categorias', 'categorias.id_categoria', '=', 'estabelecimentos_cnpj.categoria_id')
            ->leftJoin('categorias_cpf', 'categorias_cpf.id_categoria', '=', 'estabelecimentos_cpf.categoria_id')
            ->select(
                'controle_licencas.numero_licenca',
                'estabelecimentos_cnpj.cnpj',
                'estabelecimentos_cpf.cpf',
                'estabelecimentos_cnpj.nome_fantasia as nome_fantasia_cnpj',
                'estabelecimentos_cpf.nome_fantasia',
                'categorias.nome_categoria as categoria_cnpj',
                'estabelecimentos_cnpj.tipo_estabelecimento as tipo_estb_cnpj',
                'categorias_cpf.nome_categoria as categoria_cpf',
                'estabelecimentos_cpf.tipo_estabelecimento as tipo_estb_cpf',
                'estabelecimentos_cnpj.categoria_id as categoria_id_cnpj',
                'estabelecimentos_cpf.categoria_id as categoria_id_cpf',
                'controle_licencas.ano',
                'estabelecimentos_cnpj.divisao_tecnica',
                'estabelecimentos_cpf.divisao_tecnica as divisao_tecnica_cpf'
            )
            ->get();
    }
}