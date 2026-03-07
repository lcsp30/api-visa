<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Documentos_cpf;

class Estabelecimentos_cpf extends Model
{

use HasFactory;
 protected $table = 'estabelecimentos_cpf';
    protected $fillable = [
        'situacao',
        'categoria',
        'nome',
        'cpf',
        'rg',
        'orgao_expedidor',
        'data_expedicao_rg',
        'escolaridade',
        'formacao_profissional',
        'registro_conselho',
        'especializacao',
        'nome_fantasia',
        'data_inicio_funcionamento',
        'endereco',
        'numero_endereco',
        'bairro',
        'complemento_endereco',
        'municipio',
        'uf',
        'cep',
        'telefone',
        'email',
        'obs',
    ];

    // public function documentosCpf()
    // {
    //     return $this->belongsTo(Documentos_cpf::class, 'documentos_id', 'id_documentos');
    // }

    public function documentosCpf(){
       return $this->hasOne(Documentos_cpf::class, 'estabelecimento_id', 'id');
    }
    
}
