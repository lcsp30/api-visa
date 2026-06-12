<?php

namespace App\Enums;

enum TipoEstabelecimentoEnum: string
{
    case CPF  = 'cpf';
    case CNPJ = 'cnpj';

    /**
     * Retorna o label amigável do tipo.
     */
    public function label(): string
    {
        return match ($this) {
            self::CPF  => 'Pessoa Física (CPF)',
            self::CNPJ => 'Pessoa Jurídica (CNPJ)',
        };
    }

    /**
     * Retorna todos os valores como array associativo.
     */
    public static function toArray(): array
    {
        return [
            self::CPF->value  => self::CPF->label(),
            self::CNPJ->value => self::CNPJ->label(),
        ];
    }

    /**
     * Verifica se o tipo é CPF.
     */
    public function isCpf(): bool
    {
        return $this === self::CPF;
    }

    /**
     * Verifica se o tipo é CNPJ.
     */
    public function isCnpj(): bool
    {
        return $this === self::CNPJ;
    }
}
