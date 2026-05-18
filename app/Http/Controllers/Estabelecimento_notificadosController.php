<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimentos_notificados;
use Exception;
use Illuminate\Http\Request;

class Estabelecimento_notificadosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       try{
            $dados = Estabelecimentos_notificados::all();

            return response()->json($dados);
       }catch(Exception $e){
            return response($e);
       }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dados = $request->all();

        try{
            Estabelecimentos_notificados::create($dados);
            
            return response()->json([
            'Menssagem' => "Sucesso criado com Sucesso!!"
            ]);
        }catch(Exception $e){
            return response($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        $id = $request->id;

        try{
        $registro = Estabelecimentos_notificados::findOrFail($id);
        $registro->delete();
        
       return response()->json([
        'message' => 'Excluído com sucesso'
        ]);

        }catch(Exception $e){
            return response($e);
        }
    }
}
