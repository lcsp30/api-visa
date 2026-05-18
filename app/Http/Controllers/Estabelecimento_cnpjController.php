<?php
namespace App\Http\Controllers;

use App\Http\Requests\LicencaEstabelecimentoCnpjRequest;
use App\Http\Requests\StoreEstabelecimentoCnpjRequest;
use App\Http\Requests\UpdateEstabelecimentoCnpjRequest;
use App\Models\Categorias;
use App\Models\Controle_licencas;
use App\Models\Doc_categoria;
use App\Models\Documentos_cnpj;
use App\Models\Estabelecimentos_cnpj;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class Estabelecimento_cnpjController extends Controller
{
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

    public function formatarCpf($cpf)
    {
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return $cpf;
    }

    return preg_replace(
        "/(\d{3})(\d{3})(\d{3})(\d{2})/",
        "$1.$2.$3-$4",
        $cpf
    );
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $situacao = ["Documentos Pendentes 📄", "Ativo✅", "Desativado", "Licença Vencida ❌", "Ativo✅ com Intimação"];

        try{
            $dados = DB::table('estabelecimentos_cnpj')
            ->leftJoin('controle_licencas', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'controle_licencas.estabelecimento_id_cnpj')
                ->whereRaw('controle_licencas.id = (
                SELECT id FROM controle_licencas 
                WHERE estabelecimento_id_cnpj = estabelecimentos_cnpj.id 
                ORDER BY ano DESC LIMIT 1
                )');
            })
            ->leftJoin('documentos_cnpj', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'documentos_cnpj.estabelecimento_id')
                ->whereRaw('documentos_cnpj.id_documento = (
                SELECT id_documento FROM documentos_cnpj 
                WHERE estabelecimento_id = estabelecimentos_cnpj.id 
                ORDER BY ano DESC LIMIT 1
             )');
            })
            ->leftJoin('categorias', 'estabelecimentos_cnpj.categoria_id', '=', 'categorias.id_categoria')
            ->leftJoin('intimacao_constatacao', function($join){
                $join->on('estabelecimentos_cnpj.id', '=', 'intimacao_constatacao.estabelecimento_id')
                ->whereRaw('intimacao_constatacao.id_intimacao_constatacao = (
                SELECT id_intimacao_constatacao FROM intimacao_constatacao
                WHERE estabelecimento_id = estabelecimentos_cnpj.id 
                AND tipo = 1
                ORDER BY ano DESC LIMIT 1
                )');            
            })
        ->select('estabelecimentos_cnpj.id', 'estabelecimentos_cnpj.cnpj', 'estabelecimentos_cnpj.nome_fantasia', 'estabelecimentos_cnpj.tipo_estabelecimento','estabelecimentos_cnpj.categoria_id', 'estabelecimentos_cnpj.situacao', 'controle_licencas.ano', 'controle_licencas.validade', 'documentos_cnpj.ano as ano_doc', 'categorias.nome_categoria', 'intimacao_constatacao.status')
        ->get();

            $dadosFormatados = $dados->map(function ($item) use ($situacao) {

                if($item->situacao != 3 && $item->ano == $item->ano_doc){

                    if (Carbon::parse($item->validade)->lessThanOrEqualTo(now())){
                    
                    Estabelecimentos_cnpj::where('id', $item->id)  
                    ->update(['situacao' => 3]);

                    return [
                    'id' => $item->id,
                    'cnpj' => $this->formatCNPJ($item->cnpj),
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[3],
                    'indexSit' => 3
                ];

                }elseif($item->situacao == 1 && $item->status == 1){
                    Estabelecimentos_cnpj::where('id', $item->id)  
                    ->update(['situacao' => 4]);

                    return [
                    'id' => $item->id,
                    'cnpj' => $this->formatCNPJ($item->cnpj),
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[4],
                    'indexSit' => 4
                    ];
                }else{
                    return [
                    'id' => $item->id,
                    'cnpj' => $this->formatCNPJ($item->cnpj),
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[$item->situacao],
                    'indexSit' => $item->situacao
                    ];
                }
                
                }

                return [
                    'id' => $item->id,
                    'cnpj' => $this->formatCNPJ($item->cnpj),
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[$item->situacao],
                    'indexSit' => $item->situacao
                    ];
            });
            return response()->json($dadosFormatados);

        }catch(Exception $e){
            return response($e);
        }
    }

    public function getEstabelecimentos_cnpj(Request $request){
            $id = $request->id;
            try{
               $dados = Estabelecimentos_cnpj::findOrFail($id);
               return response()->json($dados);
            }catch(Exception $e){
                return response()->json([
                    "res" => "Erro ao Buscar Dados"
                ], 201);
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
        $categoria = $dados['categoria_id'];
        $dados['situacao'] = 0; 

        try{
            $resposta = DB::transaction(function () use ($categoria, $dados){
            $estabelecimento = Estabelecimentos_cnpj::create($dados);

            $doc_categoria = Doc_categoria::where('categoria_id', $categoria)
            ->get(['nome_doc', 'fixo']);

            $ano = now()->year;

            foreach($doc_categoria as $d){
               Documentos_cnpj::create([
                    'estabelecimento_id' => $estabelecimento->id,
                    'ano' => $ano,
                    'nome_doc' => $d->nome_doc,
                    'doc_local' => null,
                    'doc_fixo' => $d->fixo,
                    'data_doc' => null
                ]);
            }
                return $estabelecimento->load('documento');
            });
            return response()->json(['estabelecimento' => $resposta], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erro ao gravar estabelecimento', 'detail' => $e->getMessage()], 500);
        }
    }

    public function licenca(LicencaEstabelecimentoCnpjRequest $request){

        $dados = $request->validated();
         $id = $dados['id'];
         $tipo = $dados['tipo'];
         $categoria = $dados['categoria'];
         $ano_doc = $dados['ano'];

         try{
         $dados = Estabelecimentos_cnpj::select('nome_fantasia', 'endereco', 'numero_endereco', 'razao_social','bairro', 'cnpj', 'divisao_tecnica', 'atividade_principal')
         ->findOrFail($id);
                $nomeEstabelecimento = $dados->nome_fantasia;
                $endereco = $dados->endereco;
                $numero = $dados->numero_endereco;
                $razaoSocial = $dados->razao_social;
                $bairro = $dados->bairro;
                $cnpj = $dados->cnpj;
                $divisaoTecnica = $dados->divisao_tecnica;
                $atividadePrincipal = $dados->atividade_principal;
                $nomeDoc = $id."licenca_cnpj.docx";
                $ano = date('Y');
                $mes = date('m');
                $dia = date('d'); 
                $mesv = 0;

                $r_licenca = Controle_licencas::where('estabelecimento_id_cnpj', $id)
                ->where('ano', $ano_doc)
                ->first();
                
               if ($r_licenca) {
                    $numeroLicenca = $r_licenca->numero_licenca;
                } else{

                if($categoria == "Drogaria e Ervanaria"){
                     $mesv = 04;
                }else{
                    $mesv = 03;
                }
                $anov = $ano + 1;

                $data = Carbon::create($anov, $mesv, 30);

                    $ultimaLicenca = Controle_licencas::join('estabelecimentos_cnpj', 'estabelecimentos_cnpj.id', '=', 'controle_licencas.estabelecimento_id_cnpj')
                        ->where('divisao_tecnica', 'estabelecimentos_cnpj.divisao_tecnica')
                        ->where('ano', $ano_doc)
                        ->lockForUpdate()
                        ->max('numero_licenca') ?? 0;

                    Controle_licencas::create([
                        'estabelecimento_id_cnpj' => $id,
                        'ano' => $ano_doc,
                        'numero_licenca' => $ultimaLicenca + 1,
                        'validade' => $data
                    ]);

                    $numeroLicenca = $ultimaLicenca + 1;
                }

                $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_licenca.docx');
                
                if($tipo == "licenca"){
                    $template->setValue('r', "\u{00A0}\u{00A0}\u{00A0}");
                    $template->setValue('l', ' X ');
                }elseif($tipo == "renovacao"){
                    $template->setValue('r', ' X ');
                    $template->setValue('l', "\u{00A0}\u{00A0}\u{00A0}");
                }

                $template->setValue('dia', $dia);
                $template->setValue('mes', $mes);
                $template->setValue('ano', $ano);
                $template->setValue('nomeEstabelecimento', mb_strtoupper($nomeEstabelecimento, 'UTF-8'));
                $template->setValue('atividadePrincipal', mb_strtoupper($atividadePrincipal, 'UTF-8'));
                $template->setValue('razaoSocial', mb_strtoupper($razaoSocial, 'UTF-8'));
                $template->setValue('endereco', mb_strtoupper($endereco, 'UTF-8'));
                $template->setValue('numero', $numero);
                $template->setValue('numeroLicenca', $numeroLicenca);
                $template->setValue('categoriaLicenca', $divisaoTecnica);
                $template->setValue('bairro', mb_strtoupper($bairro, 'UTF-8'));
                $template->setValue('cnpj', $this->formatCNPJ($cnpj));
                $template->setValue('anoV', $ano + 1);

                if($categoria == "Drogaria e Ervanaria"){
                     $template->setValue('mesV', "ABRIL");
                }else{
                    $template->setValue('mesV', "MARÇO ");
                }

                $template->saveAs(__DIR__ . '/../../../public/storage/documentos/' . $nomeDoc);

                $caminho = storage_path('app/public/documentos/' . $nomeDoc);

                return response()->download($caminho);
                
         }catch(Exception $e){

            return response($e);

         }
    }

    public function requerimento(Request $request, String $id){
    $dadosInputs = $request->dados;

     try{
        $dados = Estabelecimentos_cnpj::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco = $dados->endereco;
        $numeroEndereco = $dados->numero_endereco;
        $razaoSocial = $dados->razao_social;
        $localidade = $dados->localidade;
        $atividadePrincipal = $dados->atividade_principal;
        $cpf = $dados->cpf;
        $cnpj = $dados->cnpj;
        $nomeDoc = $id."requerimento_cnpj.docx";
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');

        $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_requerimento_doc_cnpj_of.docx');

        foreach($dadosInputs as $valor){
            $template->setValue($valor, 'X');
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
        $template->setValue('razaoSocial', $razaoSocial);
        $template->setValue('endereco', $endereco);
        $template->setValue('atividadePrincipal', $atividadePrincipal);
        $template->setValue('numeroEndereco', $numeroEndereco);
        $template->setValue('localidade', $localidade);
        $template->setValue('cpf', $this->formatarCpf($cpf));
        $template->setValue('cnpj', $this->formatCNPJ($cnpj));

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Se a variável não foi substituída, ela ainda existe.
            // Define como string vazia para remover o placeholder.
            $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
        }

        $template->saveAs(__DIR__ . '/../../../public/storage/documentos/' . $nomeDoc);

        $caminho = storage_path('app/public/documentos/' . $nomeDoc);

        $soffice = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';
        $outDir = storage_path('app/public/documentos/');

        $comando = "$soffice --headless --convert-to pdf \"$caminho\" --outdir \"$outDir\"";

        exec($comando);

        $pdf = "storage/documentos/". $id ."requerimento_cnpj.pdf";

        $link = asset($pdf);

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
