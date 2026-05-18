<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doc_categoria_cpf extends Model
{
    protected $table = 'doc_categoria_cpf';

    protected $fillable = [
    'categoria_id',
    'nome_doc',
    'fixo'
    ];
}
