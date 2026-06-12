<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstabelecimentoNotificadosResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'nome_estabelecimento' => $this->nome_estabelecimento,
            'nome_proprietario'  => $this->nome_proprietario,
            'contato'            => $this->contato,
            'data_notificacao'   => $this->data_notificacao,
            'situacao'           => $this->situacao,
        ];
    }
}
