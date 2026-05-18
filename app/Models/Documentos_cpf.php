<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Estabelecimentos_cpf;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Documentos_cpf extends Model
{

 use HasFactory;
    //
    protected $table = 'documentos_cpf';
    protected $primaryKey = 'id_documentos';
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

     public $timestamps = false;

     public function estabecimentoCpf()
    {
        return $this->belongsTo(Estabelecimentos_cpf::class, 'estabelecimento_id', 'id');
    }


}
