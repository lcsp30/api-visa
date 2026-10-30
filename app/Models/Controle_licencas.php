<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Controle_licencas extends Model
{
    protected $table = 'controle_licencas';
    protected $primaryKey = 'id';
    protected $fillable = [
    'estabelecimento_id_cnpj',
    'estabelecimento_id_cpf',
    'ano', 
    'numero_licenca',
    'validade',
    'divisao_tecnica'
    ];
    public $timestamps = false;

    public function estabelecimentoCnpj(){
        return $this->belongsTo(Estabelecimentos_cnpj::class, 'estabelecimento_id_cnpj');
    }

    public function estabelecimentoCpf(){
        return $this->belongsTo(Estabelecimentos_cpf::class, 'estabelecimento_id_cpf');
    }

    
}
