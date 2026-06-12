<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_cpf', function (Blueprint $table) {
            $table->unsignedInteger('id_documento', true)->autoIncrement();
            $table->unsignedInteger('estabelecimento_id');
            $table->char('ano', 4)->nullable();
            $table->string('nome_doc', 150);
            $table->string('doc_local', 200)->nullable();
            $table->tinyInteger('doc_fixo')->nullable();
            $table->date('data_doc')->nullable();
            $table->string('url', 500)->nullable();
            $table->tinyInteger('status')->nullable();

            $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos_cpf')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_cpf');
    }
};