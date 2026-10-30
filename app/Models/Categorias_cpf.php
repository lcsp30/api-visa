<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorias_cpf extends Model
{
    protected $table = 'categorias_cpf';

    protected $primaryKey = 'id_categoria';

    public $timestamps = false;

    protected $fillable = [
        'nome_categoria'
    ];

    public function doc_categoria()
    {
        return $this->hasMany(Doc_categoria_cpf::class, 'categoria_id', 'id_categoria');
    }

    public function estabelecimentos()
    {
        return $this->hasMany(Estabelecimentos_cpf::class, 'categoria_id', 'id_categoria');
    }
}
