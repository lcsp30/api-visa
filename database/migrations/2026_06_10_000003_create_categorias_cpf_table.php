<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_cpf', function (Blueprint $table) {
            $table->integer('id_categoria', true)->autoIncrement();
            $table->string('nome_categoria', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_cpf');
    }
};