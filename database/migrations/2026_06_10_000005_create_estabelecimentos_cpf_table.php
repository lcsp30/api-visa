<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimentos_cpf', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->autoIncrement();
            $table->integer('situacao')->default(0);
            $table->integer('categoria_id');
            $table->string('tipo_estabelecimento', 70)->nullable();
            $table->string('nome', 150);
            $table->char('cpf', 11);
            $table->string('rg', 20);
            $table->string('orgao_expedidor', 30);
            $table->date('data_expedicao_rg');
            $table->string('escolaridade', 80)->nullable();
            $table->string('formacao_profissional', 120)->nullable();
            $table->string('registro_conselho', 50)->nullable();
            $table->string('especializacao', 120)->nullable();
            $table->string('nome_fantasia', 150)->nullable();
            $table->string('atividade_principal', 200)->nullable();
            $table->string('divisao_tecnica', 10)->nullable();
            $table->date('data_inicio_funcionamento')->nullable();
            $table->string('endereco', 180)->nullable();
            $table->string('localidade', 150)->nullable();
            $table->string('numero_endereco', 10)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('complemento_endereco', 120)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->char('cep', 8)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('obs', 1000)->nullable();
            $table->timestamps();

            $table->unique('cpf');
            $table->foreign('categoria_id')->references('id_categoria')->on('categorias_cpf')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos_cpf');
    }
};