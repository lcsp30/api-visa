<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimentos_notificados', function (Blueprint $table) {
            $table->id();
            $table->string('nome_estabelecimento', 120)->nullable();
            $table->string('nome_proprietario', 100)->nullable();
            $table->string('contato', 11)->nullable();
            $table->date('data_notificacao')->nullable();
            $table->string('situacao', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos_notificados');
    }
};