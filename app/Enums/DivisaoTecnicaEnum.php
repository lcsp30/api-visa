<?php

namespace App\Enums;

enum DivisaoTecnicaEnum: string
{
    case DCQA  = 'DCQA';
    case DCSEP = 'DCSEP';
    case DCDM  = 'DCDM';
    case DCSHT = 'DCSHT';

    /**
     * Retorna o label amigável da divisão técnica.
     */
    public function label(): string
    {
        return match ($this) {
            self::DCQA  => 'DCQA - Alimentos',
            self::DCSEP => 'DCSEP - Estabelecimentos de Saúde',
            self::DCDM  => 'DCDM - Medicamentos',
            self::DCSHT => 'DCSHT - Saúde do Trabalhador',
        };
    }

    /**
     * Retorna todos os valores como array associativo.
     */
    public static function toArray(): array
    {
        return [
            self::DCQA->value  => self::DCQA->label(),
            self::DCSEP->value => self::DCSEP->label(),
            self::DCDM->value  => self::DCDM->label(),
            self::DCSHT->value => self::DCSHT->label(),
        ];
    }
}
