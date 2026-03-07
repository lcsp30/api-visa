<?php

namespace App\Http\Controllers;

use App\Models\Documentos_cpf;
use App\Models\Estabelecimentos_cpf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Estabelecimento_cpfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      $dados = Estabelecimentos_cpf::all();

      return response()->json([
      'estabelecimentos' => $dados
      ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $dados = $request->all();

         $result = DB::transaction(function () use ($dados) {
            // 1) cria documento

            // 2) cria estabelecimento já com documentos_id
            $estabelecimento = Estabelecimentos_cpf::create(array_merge($dados, [
                'situacao' => 'Documentos Pendentes 📄',
            ]));

             $documento = Documentos_cpf::create([
             'estabelecimento_id' => $estabelecimento->id,
             'ano' => '2026',
             ]);

            // opcional: já devolver com relacionamento carregado
            return $estabelecimento->load('documentosCpf');
        });

    return response()->json([
        'message' => 'Estabelecimento criado com documento vinculado.',
        'data' => $result
    ], 201);
}

/**
 * Display the specified resource.
 */
public function show(string $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $id = $request->query('id');

        $registro = Estabelecimentos_cpf::findOrFail($id);

        $registro->delete();

       return response()->json([
        'message' => 'Excluído com sucesso'
    ]);

    }
}
