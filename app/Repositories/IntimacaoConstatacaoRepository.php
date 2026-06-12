<?php

namespace App\Repositories;

use App\Models\Intimacao_constatacao;
use App\Models\Intimacao_constatacao_cpf;
use App\Enums\TipoEstabelecimentoEnum;

class IntimacaoConstatacaoRepository
{
    // Finaliza (status = false) uma intimação/constatação por ID, conforme tipo de estabelecimento.
    public function finalizar(string $tipoEstabelecimento, int $id): void
    {
        if ($tipoEstabelecimento === TipoEstabelecimentoEnum::CNPJ->value) {
            Intimacao_constatacao::updateOrCreate(
                ['id_intimacao_constatacao' => $id],
                ['status' => false]
            );
        } else {
            Intimacao_constatacao_cpf::updateOrCreate(
                ['id_intimacao_constatacao' => $id],
                ['status' => false]
            );
        }
    }

    // Cria ou atualiza uma intimação/constatação com base no tipo de estabelecimento.
    public function criarOuAtualizar(string $tipoEstabelecimento, array $dados): void
    {
        if ($tipoEstabelecimento === TipoEstabelecimentoEnum::CNPJ->value) {
            Intimacao_constatacao::updateOrCreate(
                [
                    'estabelecimento_id' => $dados['estabelecimento_id'],
                    'ano'                => $dados['ano'],
                    'tipo'               => $dados['tipo'],
                ],
                [
                    'estabelecimento_id' => $dados['estabelecimento_id'],
                    'ano'                => $dados['ano'],
                    'status'             => true,
                    'tipo'               => $dados['tipo'],
                    'descricao'          => $dados['descricao'],
                    'data_inicial'       => date('Y-m-d'),
                    'data_expiracao'     => $dados['data_expiracao'],
                ]
            );
        } else {
            Intimacao_constatacao_cpf::updateOrCreate(
                [
                    'estabelecimento_id' => $dados['estabelecimento_id'],
                    'ano'                => $dados['ano'],
                    'tipo'               => $dados['tipo'],
                ],
                [
                    'estabelecimento_id' => $dados['estabelecimento_id'],
                    'ano'                => $dados['ano'],
                    'status'             => true,
                    'tipo'               => $dados['tipo'],
                    'descricao'          => $dados['descricao'],
                    'data_inicial'       => date('Y-m-d'),
                    'data_expiracao'     => $dados['data_expiracao'],
                ]
            );
        }
    }

    // Busca intimações/constatações ativas (status = 1) de um estabelecimento em um ano.
    public function buscarIntimacoesAtivas(string $tipoEstabelecimento, int $estabelecimentoId, int $ano)
    {
        if ($tipoEstabelecimento === TipoEstabelecimentoEnum::CNPJ->value) {
            return Intimacao_constatacao::select(
                'id_intimacao_constatacao', 'estabelecimento_id', 'tipo',
                'descricao', 'data_inicial', 'data_expiracao', 'status'
            )
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->where('status', 1)
            ->get();
        }

        return Intimacao_constatacao_cpf::select(
            'id_intimacao_constatacao', 'estabelecimento_id', 'tipo',
            'descricao', 'data_inicial', 'data_expiracao', 'status'
        )
        ->where('estabelecimento_id', $estabelecimentoId)
        ->where('ano', $ano)
        ->where('status', 1)
        ->get();
    }
}