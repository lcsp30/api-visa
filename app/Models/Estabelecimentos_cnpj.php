<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Documentos_cnpj;
use App\Models\Categorias;

class Estabelecimentos_cnpj extends Model
{
    protected $table = "estabelecimentos_cnpj";

    protected $fillable = [
    'categoria_id',
    'situacao',
    'cnpj',
    'razao_social',
    'atividade_principal',
    'divisao_tecnica',
    'tipo_estabelecimento',
    'nome_fantasia',
    'natureza_juridica',
    'data_inicio_funcionamento',
    'insc_estadual',
    'insc_municipal',
    'cnes',
    'nome_responsavel',
    'cpf',
    'rg',
    'orgao_expedidor',
    'data_expedicao_rg',
    'endereco',
    'numero_endereco',
    'localidade',
    'bairro',
    'municipio',
    'cep',
    'telefone',
    'email',
    'obs'
    ];

    public function documento(){
        return $this->hasMany(Documentos_cnpj::class, 'estabelecimento_id', 'id');
    }

    public function licencaCnpj() {
        return $this->hasMany(Controle_licencas::class, 'estabelecimento_id_cnpj', 'id');
    }

    public function categoriaEstabelecimento(){
        return $this->belongsTo(Categorias::class,'categoria_id', 'id_categoria');
    }

    // public function documentosCnpj(){
    //     return $this->hasMany(Documentos_cnpj::class, 'estabelecimento_id', 'id');
    // }

     public function intimacoes()
    {
        return $this->hasMany(Intimacao_constatacao::class, 'estabelecimento_id', 'id')->where('status', 1);
    }
}
