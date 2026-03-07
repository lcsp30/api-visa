<?php

namespace App\Http\Controllers;

use App\Models\Documentos_cpf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Documentos_cpfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $id = $request->id;
        $ano = $request->ano;
        $categoria = $request->categoria;

        if($id && $ano && $categoria){

        if($categoria == 1){

        try{

            $dados = Documentos_cpf::query()
            ->select('ano', 'cpf', 'rg', 'alvara_prefeitura', 'carteira_conselho_profissional', 'comprovante_conselho', 'licenca_bombeiros', 'data_cpf', 'data_rg', 'data_alvara_prefeitura', 'data_carteira_conselho_profissional','data_comprovente_conselho','data_licenca_bombeiros')
            ->where('estabelecimento_id', $id)
            ->where('ano', $ano)
            ->first();

           return response()->json([
                 [
                  'nome' => 'CPF',
                  'valor' => $dados->cpf,
                  'nomeInput' => 'cpf',
                  'urlDoc' => asset('storage/' . $dados->cpf),
                  'data' => date('d/m/Y', strtotime($dados->data_cpf))
                 ],
                 [
                  'nome' => 'RG',
                  'valor' => $dados->rg,
                  'nomeInput' => 'rg',
                  'urlDoc' => asset('storage/' . $dados->rg),
                  'data' => date('d/m/Y', strtotime($dados->data_rg))
                 ],
                 [
                  'nome' => 'Alvará de Localização do Ano em Curso',
                  'valor' => $dados->alvara_prefeitura,
                  'nomeInput' => 'alvara_prefeitura',
                  'urlDoc' => asset('storage/' . $dados->alvara_prefeitura),
                  'data' => date('d/m/Y', strtotime($dados->data_alvara_prefeitura))
                 ],
                 [
                  'nome' => 'Carteira do Conselho Profissional',
                  'valor' => $dados->carteira_conselho_profissional,
                  'nomeInput' => 'carteira_conselho_profissional',
                  'urlDoc' => asset('storage/' . $dados->carteira_conselho_profissional),
                  'data' => date('d/m/Y', strtotime($dados->data_carteira_conselho_profissional))
                 ],
                 [
                  'nome' => 'Comprovante do Conselho',
                  'valor' => $dados->comprovante_conselho,
                  'nomeInput' => 'comprovante_conselho',
                  'urlDoc' => asset('storage/' . $dados->comprovante_conselho),
                  'data' => date('d/m/Y', strtotime($dados->data_comprovente_conselho))
                 ],
                 [
                  'nome' => 'Licença Corpo de Bombeiro',
                  'valor' => $dados->licenca_bombeiros,
                  'nomeInput' => 'licenca_bombeiros',
                  'urlDoc' => asset('storage/' . $dados->licenca_bombeiros),
                  'data' => date('d/m/Y', strtotime($dados->data_licenca_bombeiros))
                 ],    
            ], 201);
        
        }catch(Exception $e){

            return response()->json([
                'Erro' => 'Erro ao buscar Documentos!!',
            ], 500);
        
        }
           


        }else{

        try{
        $dados = Documentos_cpf::query()
            ->select('ano', 'cpf', 'rg', 'alvara_prefeitura','licenca_bombeiros', 'data_cpf', 'data_rg', 'data_alvara_prefeitura', 'data_licenca_bombeiros')
            ->where('estabelecimento_id', $id)
            ->where('ano', $ano)
            ->first(); 

           return response()->json([
                 [
                  'nome' => 'CPF',
                  'valor' => $dados->cpf,
                  'nomeInput' => 'cpf',
                  'urlDoc' => asset('storage/' . $dados->cpf),
                  'data' => date('d/m/Y', strtotime($dados->data_cpf))
                 ],
                 [
                  'nome' => 'RG',
                  'valor' => $dados->rg,
                  'nomeInput' => 'rg',
                  'urlDoc' => asset('storage/' . $dados->rg),
                  'data' => date('d/m/Y', strtotime($dados->data_rg))
                 ],
                 [
                  'nome' => 'Alvará de Localização do Ano em Curso',
                  'valor' => $dados->alvara_prefeitura,
                  'nomeInput' => 'alvara_prefeitura',
                  'urlDoc' => asset('storage/' . $dados->alvara_prefeitura),
                  'data' => date('d/m/Y', strtotime($dados->data_alvara_prefeitura))
                 ],
                 [
                  'nome' => 'Licença Corpo de Bombeiros',
                  'valor' => $dados->licenca_bombeiros,
                  'nomeInput' => 'licenca_bombeiros',
                  'urlDoc' => asset('storage/' . $dados->licenca_bombeiros),
                  'data' => date('d/m/Y', strtotime($dados->data_licenca_bombeiros))
                 ]
            ], 201);
        }catch(Exception $e){
             return response()->json([
                'Erro' => 'Erro ao buscar Documentos!!',
            ], 500);
            }
         }

        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->hasFile('arquivo')){
        $arquivo = $request->file('arquivo')->store('documentos', 'public');
        $id = $request->estabelecimento_id;
        $ano = $request->ano;
        $nomeDoc = $request->nomeInput;

        $nomeData = "data_" . $nomeDoc;
        
        
        try{
            $doc = Documentos_cpf::updateOrCreate([
                'estabelecimento_id' => $id,
                'ano' => $ano,
            ],
            [
                $nomeDoc => $arquivo,
                $nomeData => date('y/m/d')
            ]);


            return response()->json([
                'Sucesso' => 'Arquivo adicionado com Sucesso!!', 
                'date' => $nomeData,
            ]);

        }catch(Exception $e){
            return response()->json([
                'Error' => 'Erro ao adicionar Arquivo!!'
            ]);
        
        }
        
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $id = $request->query('id');

        if($id){
            $dados = Documentos_cpf::query()->select('ano')->where('estabelecimento_id', $id)->get();
            return response()->json($dados, 201);
        }else{
            return response()->json([
            'Menssagem' => 'Erro ao Buscar Dados!!'
            ]);
        }
    }

    public function download(Request $request){
        $docUrl = $request->url;
        $caminho = storage_path("app/public/" . $docUrl);

        try{
            return response()->download($caminho);
        }catch(Exception $e){
            return response()->json([
                'Error' => 'Erro ao fazer Download!!!',
            ]);
        }
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
        $ano = $request->ano;
        $docNome = $request->docNome;
        $docData = $request->docData;

        try{
        Documentos_cpf::query()
        ->where('estabelecimento_id',$id)
        ->where('ano',$ano)
        ->update([
            $docNome => null,
            $docData => null
        ]);

        return response()->json([
            'Menssagem' => 'Documento excluido com Sucessso!!'
        ]);
        }catch(Exception $e){
        return response()->json([
        'Menssagem' => 'Falha ao Excluir'
        ], 400);
        }
    }
}
