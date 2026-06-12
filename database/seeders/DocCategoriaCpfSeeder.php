<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocCategoriaCpfSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('doc_categoria_cpf')->insert([
            // Profissional Liberal (1)
            ['categoria_id' => 1, 'nome_doc' => 'Requerimento', 'fixo' => 0],
            ['categoria_id' => 1, 'nome_doc' => 'CPF', 'fixo' => 1],
            ['categoria_id' => 1, 'nome_doc' => 'RG', 'fixo' => 1],
            ['categoria_id' => 1, 'nome_doc' => 'Alvará de Tributos', 'fixo' => 0],
            ['categoria_id' => 1, 'nome_doc' => 'Carteira do Conselho Profissional', 'fixo' => 1],
            ['categoria_id' => 1, 'nome_doc' => 'Comprovante de Pagamento do Conselho', 'fixo' => 0],
            ['categoria_id' => 1, 'nome_doc' => 'Licença do Corpo de Bombeiros', 'fixo' => 0],
            ['categoria_id' => 1, 'nome_doc' => 'Licença de Funcionamento', 'fixo' => 0],
            // Autônomo (2)
            ['categoria_id' => 2, 'nome_doc' => 'CPF', 'fixo' => 1],
            ['categoria_id' => 2, 'nome_doc' => 'RG', 'fixo' => 1],
            ['categoria_id' => 2, 'nome_doc' => 'Alvará de Tributos', 'fixo' => 0],
            ['categoria_id' => 2, 'nome_doc' => 'Licença do Corpo de Bombeiros', 'fixo' => 0],
            ['categoria_id' => 2, 'nome_doc' => 'Licença de Funcionamento', 'fixo' => 0],
        ]);
    }
}