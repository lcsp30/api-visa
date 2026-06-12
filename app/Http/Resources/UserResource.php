<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'name_user'   => $this->name_user,
            'nivel_acesso' => $this->nivel_acesso,
            'created_at'  => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'  => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
