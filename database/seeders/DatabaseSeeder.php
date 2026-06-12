<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriasSeeder::class,
            CategoriasCpfSeeder::class,
            DocCategoriaSeeder::class,
            DocCategoriaCpfSeeder::class,
        ]);
    }
}