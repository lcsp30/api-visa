<?php

namespace App\Helpers;

class FormatadorHelper
{
    /**
     * Formata CNPJ: 00.000.000/0000-00
     */
    public static function formatCNPJ($cnpj): string
    {
        $cnpj = preg_replace("/\D/", "", $cnpj);
        return preg_replace(
            "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/",
            "$1.$2.$3/$4-$5",
            $cnpj
        );
    }

    /**
     * Formata CPF: 000.000.000-00
     */
    public static function formatarCpf($cpf): string
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
}
