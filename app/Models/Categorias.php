<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Doc_categoria;

class Categorias extends Model
{
   protected $table = 'categorias'; 
   protected $primaryKey = 'id_categoria';
   protected $fillable = [
    'nome_categoria'
   ];

   public function doc_categoria(){
    return $this->hasMany(Doc_categoria::class, 'categoria_id', 'id_categoria');
   }

   public function estabelecimentoCategoria(){
      return $this->hasOne(Estabelecimentos_cnpj::class, 'categoria_id', 'id_categoria');
   }
}

