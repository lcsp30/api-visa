<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intimacao_constatacao_cpf extends Model
{
    protected $table = 'intimacao_constatacao_cpf';
    protected $primaryKey = 'id_intimacao_constatacao';
    protected $fillable = [
    'estabelecimento_id',
    'status',
    'ano',
    'tipo',
    'descricao',
    'data_inicial',
    'data_expiracao',
    ];

    public $timestamps = false;
}
