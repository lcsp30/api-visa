<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categorias')->insert([
            ['id_categoria' => 1, 'nome_categoria' => 'Geral'],
            ['id_categoria' => 3, 'nome_categoria' => 'Loja de Produtos Agropecuários'],
            ['id_categoria' => 4, 'nome_categoria' => 'Laboratório de Análises Clínicas'],
            ['id_categoria' => 5, 'nome_categoria' => 'Estabelecimentos Óticos'],
            ['id_categoria' => 6, 'nome_categoria' => 'Empresa de Laticínios'],
            ['id_categoria' => 7, 'nome_categoria' => 'Drogaria e Ervanaria'],
            ['id_categoria' => 8, 'nome_categoria' => 'Serviços de Desratização, Desinsetização e Descupinização'],
            ['id_categoria' => 9, 'nome_categoria' => 'Cozinha Industrial / Refeitório'],
            ['id_categoria' => 10, 'nome_categoria' => 'Consultório Médico ou Odontológico'],
            ['id_categoria' => 11, 'nome_categoria' => 'Clínica de Fisioterapia'],
            ['id_categoria' => 12, 'nome_categoria' => 'Ambulatório Médico'],
            ['id_categoria' => 13, 'nome_categoria' => 'Academia'],
            ['id_categoria' => 14, 'nome_categoria' => 'Posto de Coleta'],
            ['id_categoria' => 15, 'nome_categoria' => 'Salão de Beleza'],
            ['id_categoria' => 16, 'nome_categoria' => 'Silo para Armazenamento de Grãos'],
        ]);
    }
}