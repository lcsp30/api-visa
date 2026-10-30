<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controle_licencas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('estabelecimento_id_cnpj')->nullable();
            $table->unsignedInteger('estabelecimento_id_cpf')->nullable();
            $table->char('ano', 4);
            $table->integer('numero_licenca');
            $table->string('divisao_tecnica', 10);
            $table->date('validade');

            $table->foreign('estabelecimento_id_cnpj')->references('id')->on('estabelecimentos_cnpj')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('estabelecimento_id_cpf')->references('id')->on('estabelecimentos_cpf')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controle_licencas');
    }
};