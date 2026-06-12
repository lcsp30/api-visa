<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimentos_cnpj', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->autoIncrement();
            $table->integer('categoria_id');
            $table->integer('situacao')->default(0);
            $table->char('cnpj', 14);
            $table->string('razao_social', 150);
            $table->string('atividade_principal', 200)->nullable();
            $table->string('divisao_tecnica', 10)->nullable();
            $table->string('tipo_estabelecimento', 70)->nullable();
            $table->string('nome_fantasia', 150)->nullable();
            $table->string('natureza_juridica', 150)->nullable();
            $table->date('data_inicio_funcionamento')->nullable();
            $table->string('insc_estadual', 30)->nullable();
            $table->string('insc_municipal', 30)->nullable();
            $table->string('cnes', 20)->nullable();
            $table->string('nome_responsavel', 150);
            $table->char('cpf', 11)->nullable();
            $table->string('rg', 20)->nullable();
            $table->string('orgao_expedidor', 30)->nullable();
            $table->date('data_expedicao_rg')->nullable();
            $table->string('endereco', 180)->nullable();
            $table->string('localidade', 150)->nullable();
            $table->string('numero_endereco', 20)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->char('cep', 8)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('obs')->nullable();
            $table->timestamps();

            $table->unique('cnpj');
            $table->foreign('categoria_id')->references('id_categoria')->on('categorias')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos_cnpj');
    }
};