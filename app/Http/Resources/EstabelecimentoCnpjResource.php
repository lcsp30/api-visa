<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstabelecimentoCnpjResource extends JsonResource
{
   private array $situacoes = [
        0 => 'Documentos Pendentes 📄',
        1 => 'Ativo ✅',
        3 => 'Licença Vencida ❌',
        4 => 'Ativo ✅ com Intimação',
    ];

     public function formatCNPJ($cnpj){
            // 1. Remove tudo que não é número
            $cnpj = preg_replace("/\D/", "", $cnpj);
            // 2. Aplica a máscara: 00.000.000/0000-00
            return preg_replace(
                "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/",
                "$1.$2.$3/$4-$5",
                $cnpj
            );
        }


    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'cnpj'         => $this->formatCNPJ($this->cnpj),
            'nome_fantasia' => $this->nome_fantasia,
            'categoria'    => $this->nome_categoria,
            'situacao'     => $this->situacoes[$this->situacao] ?? 'Desconhecido',
            'indexSit'     => $this->situacao,
        ];
    }
}
