<?php

namespace App\Http\Resources;

use App\Helpers\FormatadorHelper;
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

    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'cnpj'         => FormatadorHelper::formatCNPJ($this->cnpj),
            'nome_fantasia' => $this->nome_fantasia,
            'categoria'    => $this->nome_categoria,
            'situacao'     => $this->situacoes[$this->situacao] ?? 'Desconhecido',
            'indexSit'     => $this->situacao,
            'categoria_id' => $this->categoria_id,
            'tipo_estb'    => $this->tipo_estabelecimento
        ];
    }
}
