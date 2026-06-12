<?php

namespace App\Enums;

enum SituacaoEstabelecimentoEnum: int
{
    case PENDENTE = 0;
    case ATIVO = 1;
    case VENCIDO = 3;
    case BLOQUEADO = 4;

    /**
     * Retorna o label amigável da situação.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDENTE  => 'Pendente',
            self::ATIVO     => 'Ativo',
            self::VENCIDO   => 'Vencido',
            self::BLOQUEADO => 'Bloqueado',
        };
    }

    /**
     * Retorna todos os valores como array associativo.
     */
    public static function toArray(): array
    {
        return [
            self::PENDENTE->value  => self::PENDENTE->label(),
            self::ATIVO->value     => self::ATIVO->label(),
            self::VENCIDO->value   => self::VENCIDO->label(),
            self::BLOQUEADO->value => self::BLOQUEADO->label(),
        ];
    }
}
