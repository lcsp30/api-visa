<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorias_cpf extends Model
{
    protected $table = 'categorias_cpf';

    protected $primaryKey = 'id_categoria';

    protected $fillable = [
    'nome_categoria'
    ];

}
