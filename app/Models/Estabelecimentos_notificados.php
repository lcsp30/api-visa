<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estabelecimentos_notificados extends Model
{
    protected $table = 'estabelecimentos_notificados';
    protected $fillable = [
    'nome_estabelecimento',
    'nome_proprietario',
    'contato',
    'data_notificacao',
    'situacao'
    ];

    public $timestamps = false;
}
