<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intimacao_constatacao_cpf', function (Blueprint $table) {
            $table->unsignedInteger('id_intimacao_constatacao', true)->autoIncrement();
            $table->unsignedInteger('estabelecimento_id');
            $table->char('ano', 4)->nullable();
            $table->tinyInteger('status');
            $table->integer('tipo')->nullable();
            $table->string('descricao', 355);
            $table->date('data_inicial')->nullable();
            $table->date('data_expiracao')->nullable();

            $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos_cpf')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intimacao_constatacao_cpf');
    }
};