<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Estabelecimentos_cnpj;

class Documentos_cnpj extends Model
{
    protected $table = 'documentos_cnpj';
    protected $fillable = [
    'estabelecimento_id',
    'ano',
    'nome_doc',
    'doc_local',
    'doc_fixo',
    'data_doc',
    'url',
    'status'
    ];

    protected $casts = [
    'doc_fixo' => 'boolean',
    ];

    public $timestamps = false;

    public function estabelecimento(){
        return $this->belongsTo(Estabelecimentos_cnpj::class, 'estabelecimento_id', 'id');
    }
}
