<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_categoria', function (Blueprint $table) {
            $table->integer('categoria_id');
            $table->string('nome_doc', 200);
            $table->tinyInteger('fixo')->nullable();

            $table->foreign('categoria_id')->references('id_categoria')->on('categorias')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_categoria');
    }
};