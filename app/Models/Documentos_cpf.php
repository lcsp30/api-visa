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
        'cpf',
        'rg',
        'alvara_prefeitura',
        'carteira_conselho_profissional',
        'comprovante_conselho',
        'licenca_bombeiros',
        'data_comprovente_conselho',
        'data_cpf',
        'data_rg',
        'data_licenca_bombeiros',
        'data_alvara_prefeitura',
        'data_carteira_conselho_profissional'
    ];

     public $timestamps = false;

    // public function estabelecimentosCpf(){
    //    return $this->hasOne(Estabelecimentos_cpf::class, 'documentos_id', 'id_documentos');
    // }

     public function estabecimentoCpf()
    {
        return $this->belongsTo(Estabelecimentos_cpf::class, 'estabelecimento_id', 'id');
    }


}
