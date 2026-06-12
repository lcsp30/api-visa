<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasCpfSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categorias_cpf')->insert([
            ['id_categoria' => 1, 'nome_categoria' => 'Profissional Liberal'],
            ['id_categoria' => 2, 'nome_categoria' => 'Autônomo'],
        ]);
    }
}