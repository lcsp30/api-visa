<?php
namespace App\Http\Controllers;

use App\Http\Requests\LicencaEstabelecimentoCnpjRequest;
use App\Http\Requests\StoreEstabelecimentoCnpjRequest;
use App\Http\Requests\UpdateEstabelecimentoCnpjRequest;
use App\Http\Resources\EstabelecimentoCnpjResource;
use App\Models\Categorias;
use App\Models\Controle_licencas;
use App\Models\Doc_categoria;
use App\Models\Documentos_cnpj;
use App\Models\Estabelecimentos_cnpj;
use App\Services\EstabelecimentoCnpjService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class Estabelecimento_cnpjController extends Controller
{

    public function __construct(EstabelecimentoCnpjService $service){
        $this->service = $service;
    }
   
    public function formatCNPJ($cnpj){
            // 1. Remove tudo que não é número
            $cnpj = preg_replace("/\D/", "", $cnpj);
            // 2. Aplica a máscara: 00.000.000/0000-00
            return preg_replace(
                "/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/",
                "$1.$2.$3/$4-$5",
                $cnpj
            );
        }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
          
            $dados = $this->service->listarTodosEsbtCnpj();
            return EstabelecimentoCnpjResource::collection($dados);

        }catch(Exception $e){
            return response($e);
        }
    }

    public function getEstabelecimentos_cnpj(String $id){
            try{
               $dados = Estabelecimentos_cnpj::findOrFail($id);
               return response()->json($dados);
            }catch(Exception $e){
                return response()->json([
                    "res" => "Erro ao Buscar Dados"
                ], 400);
            }
        }

    public function getCategorias()
    {
        try{
             $dados = Categorias::all();
             return response()->json($dados);
             
        }catch(Exception $e){
            return response($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstabelecimentoCnpjRequest $request)
    {
        $dados = $request->validated();
        try{
        
           $estabelecimento = $this->service->criarEstabelecimentoComDocs($dados);
            return response()->json(['estabelecimento' => $estabelecimento], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao gravar estabelecimento', 'detail' => $e->getMessage()], 500);
        }
    }

    public function licenca(LicencaEstabelecimentoCnpjRequest $request){

        $dados = $request->validated();

         try{
                $caminhoDownload = $this->service->baixarLicenca($dados);
                return response()->download($caminhoDownload);
                
         }catch(Exception $e){

            return response($e);

         }
    }

    public function requerimento(Request $request, String $id){
        $dadosInputs = $request->dados;
     try{
        $link = $this->service->gerarRequerimento($dadosInputs, $id);
        return response()->json([
            'link' => $link
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
    public function update(UpdateEstabelecimentoCnpjRequest $request, string $id)
    {
        $dados = $request->validated();

        try{
            Estabelecimentos_cnpj::where('id', $id)
            ->update($dados);

            return response()->json([
            'res' => "Atualizado com Sucesso!"
            ], 200);
        }catch(Exception $e){
            return response($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
