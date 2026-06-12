<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentosResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_documento'    => $this->id_documento,
            'estabelecimento_id' => $this->estabelecimento_id,
            'ano'             => $this->ano,
            'nome_doc'        => $this->nome_doc,
            'doc_local'       => $this->doc_local,
            'doc_fixo'        => $this->doc_fixo,
            'status'          => $this->status,
            'data_doc'        => $this->data_doc?->format('d/m/Y'),
            'url'             => $this->url,
        ];
    }
}
