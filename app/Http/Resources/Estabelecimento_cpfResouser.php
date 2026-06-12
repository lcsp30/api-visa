<?php

namespace App\Http\Resources;

use App\Helpers\FormatadorHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Estabelecimento_cpfResouser extends JsonResource
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
            'cpf'          => FormatadorHelper::formatarCpf($this->cpf),
            'nome'         => $this->nome,
            'nome_fantasia' => $this->nome_fantasia,
            'categoria'    => $this->nome_categoria,
            'categoria_id' => $this->categoria_id,
            'situacao'     => $this->situacoes[$this->situacao] ?? 'Desconhecido',
            'indexSit'     => $this->situacao,
            'tipo_estb'    => $this->tipo_estabelecimento
        ];
    }
}
