<?php

namespace Database\Seeders;

use App\Models\Users;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Users::updateOrCreate(
            ['name_user' => 'admin'],          // chave de busca (evita duplicar)
            [
                'name'         => 'Admin',
                'name_user'    => 'admin',
                'password'     => Hash::make('admin123'), // troque pela senha desejada
                'nivel_acesso' => 1,            // 1 = Admin
            ]
        );
    }
}
