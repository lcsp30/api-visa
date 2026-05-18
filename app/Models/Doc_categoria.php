<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Categorias;

class Doc_categoria extends Model
{
   protected $table = 'doc_categoria'; 

   protected $fillable = [
    'categoria_id',
    'nome_doc',
    'fixo'
   ];

   protected $casts = [
   'fixo' => 'boolean'
   ];

   public $timestamps = false;

   public function categorias(){
      return $this->belongsTo(Categorias::class, 'categoria_id', 'id_categoria');  
   }
}
