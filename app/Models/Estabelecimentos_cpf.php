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
        'categoria_id',
        'tipo_estabelecimento',
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
        'atividade_principal',
        'divisao_tecnica',
        'data_inicio_funcionamento',
        'endereco',
        'localidade',
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

    public function categoriaEstabelecimentoCpf(){
        return $this->belongsTo(Categorias_cpf::class, 'categoria_id', 'id_categoria');
    }

    public function documentos(){
       return $this->hasMany(Documentos_cpf::class, 'estabelecimento_id', 'id');
    }

      public function intimacoes()
    {
        return $this->hasMany(Intimacao_constatacao_cpf::class, 'estabelecimento_id', 'id')->where('status', 1);
    }
    
}
