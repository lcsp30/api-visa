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
    'validade'
    ];
    public $timestamps = false;

    public function estabelecimentoCnpj(){
        return $this->hasOne(Estabelecimentos_cnpj::class, 'estabelecimento_id_cnpj', 'id');
    }
}
