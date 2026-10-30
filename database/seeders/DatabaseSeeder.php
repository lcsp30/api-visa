<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategoriasSeeder::class,
            CategoriasCpfSeeder::class,
            DocCategoriaSeeder::class,
            DocCategoriaCpfSeeder::class,
        ]);
    }
}