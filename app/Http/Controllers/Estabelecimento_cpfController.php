<?php

namespace App\Http\Controllers;

use App\Models\Categorias_cpf;
use App\Models\Controle_licencas;
use App\Models\Doc_categoria_cpf;
use App\Models\Documentos_cpf;
use App\Models\Estabelecimentos_cpf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;

class Estabelecimento_cpfController extends Controller
{

 public function cpfFormat($value){
            $cpfFun = preg_replace('/[^0-9]/', '', $value);

            if(strlen($cpfFun) != 11){
                return $value;
            }

            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfFun);
        }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    $situacao = ["Documentos Pendentes 📄", "Ativo✅", "Desativado", "Licença Vencida ❌", "Ativo✅ com Intimação"];

     try{
            $dados = DB::table('estabelecimentos_cpf')
            ->leftJoin('controle_licencas', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'controle_licencas.estabelecimento_id_cpf')
                ->whereRaw('controle_licencas.id = (
                SELECT id FROM controle_licencas 
                WHERE estabelecimento_id_cpf = estabelecimentos_cpf.id 
                ORDER BY ano DESC LIMIT 1
                )');
            })
            ->leftJoin('documentos_cpf', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'documentos_cpf.estabelecimento_id')
                ->whereRaw('documentos_cpf.id_documento = (
                SELECT id_documento FROM documentos_cpf 
                WHERE estabelecimento_id = estabelecimentos_cpf.id 
                ORDER BY ano DESC LIMIT 1
             )');
            })
            ->leftJoin('categorias_cpf', 'estabelecimentos_cpf.categoria_id', '=', 'categorias_cpf.id_categoria')
            ->leftJoin('intimacao_constatacao_cpf', function($join){
                $join->on('estabelecimentos_cpf.id', '=', 'intimacao_constatacao_cpf.estabelecimento_id')
                ->whereRaw('intimacao_constatacao_cpf.id_intimacao_constatacao = (
                SELECT id_intimacao_constatacao FROM intimacao_constatacao_cpf
                WHERE estabelecimento_id = estabelecimentos_cpf.id 
                AND tipo = 1
                ORDER BY ano DESC LIMIT 1
                )');            
            })
        ->select('estabelecimentos_cpf.id', 'estabelecimentos_cpf.cpf', 'estabelecimentos_cpf.nome','estabelecimentos_cpf.nome_fantasia', 'estabelecimentos_cpf.tipo_estabelecimento','estabelecimentos_cpf.categoria_id', 'estabelecimentos_cpf.situacao', 'controle_licencas.ano', 'controle_licencas.validade', 'documentos_cpf.ano as ano_doc', 'categorias_cpf.nome_categoria', 'intimacao_constatacao_cpf.status')
        ->get();

        $dadosFormatados = $dados->map(function ($item) use ($situacao) {

                if($item->situacao != 3 && $item->ano == $item->ano_doc){

                 if (Carbon::parse($item->validade)->lessThanOrEqualTo(now())){
                    
                    Estabelecimentos_cpf::where('id', $item->id)  
                    ->update(['situacao' => 3]);

                    return [
                   'id' => $item->id,
                    'cpf' => $this->cpfFormat($item->cpf),
                    'nome' => $item->nome,
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[3],
                    'indexSit' => 3
                ];

                }elseif($item->situacao == 1 && $item->status == 1){
                    Estabelecimentos_cpf::where('id', $item->id)  
                    ->update(['situacao' => 4]);

                    return [
                    'id' => $item->id,
                    'cpf' => $this->cpfFormat($item->cpf),
                    'nome' => $item->nome,
                    'nome_fantasia' => $item->nome_fantasia,
                    'categoria' => $item->nome_categoria,
                    'categoria_id' => $item->categoria_id,
                    'tipo_estb' => $item->tipo_estabelecimento,
                    'situacao' => $situacao[4],
                    'indexSit' => 4
                    ];
                }
                
                }

                return [
                    'id' => $item->id,
                    'cpf' => $this->cpfFormat($item->cpf),
                    'nome' => $item->nome,
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

     public function getEstabelecimentos_cpf(Request $request){
            $id = $request->id;
            try{
               $dados = Estabelecimentos_cpf::findOrFail($id);

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
             $dados = Categorias_cpf::all();
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
       $categoria = $request->categoria_id;

       try{
         $resultado = DB::transaction(function () use ($categoria, $dados) {
            // 1) cria estabelecimento já com documentos_id
            $estabelecimento = Estabelecimentos_cpf::create(array_merge($dados, [
                'situacao' => 0,
            ]));

            $doc_categoria = Doc_categoria_cpf::where('categoria_id', $categoria)
            ->get(['nome_doc', 'fixo']);

            $ano = now()->year;

            // 2) cria documento
             foreach($doc_categoria as $doc){
             Documentos_cpf::create([
             'estabelecimento_id' => $estabelecimento->id,
             'ano' => $ano,
             'nome_doc' => $doc->nome_doc,
             'doc_fixo' => $doc->fixo,
             ]);
             }
            // opcional: já devolver com relacionamento carregado
            return $estabelecimento->load('documentosCpf');
        });

            return response()->json([
                'Sucesso' => 'Estabelecimento criado com documento vinculado.',
                'Dados' => $resultado
            ], 201);

       }catch(Exception $e){
        return response($e);
       }
}

 public function licenca_cpf(Request $request){
         $id = $request->id;
         $tipo = $request->tipo;
         $ano_doc = $request->ano;

        try{
        $dados = Estabelecimentos_cpf::select('nome_fantasia', 'endereco', 'numero_endereco','bairro','divisao_tecnica', 'atividade_principal')
         ->findOrFail($id);
            // $dados = Estabelecimentos_cnpj::findOrFail($id);
                $nomeEstabelecimento = $dados->nome_fantasia;
                $endereco = $dados->endereco;
                $numero = $dados->numero_endereco;
                $bairro = $dados->bairro;
                $divisaoTecnica = $dados->divisao_tecnica;
                $atividadePrincipal = $dados->atividade_principal;
                $nomeDoc = $id."licenca_cnpj.docx";
                $ano = date('Y');
                $mes = date('m');
                $dia = date('d'); 

                $r_licenca = Controle_licencas::where('estabelecimento_id_cpf', $id)
                ->where('ano', $ano_doc)
                ->first();
                
               if ($r_licenca) {
                    $numeroLicenca = $r_licenca->numero_licenca;
                } else{
                $anov = $ano + 1;

                $data = Carbon::create($anov, 03, 30);

                    $ultimaLicenca = Controle_licencas::join('estabelecimentos_cpf', 'estabelecimentos_cpf.id', '=', 'controle_licencas.estabelecimento_id_cpf')
                        ->where('divisao_tecnica', 'estabelecimentos_cpf.divisao_tecnica')
                        ->where('ano', $ano_doc)
                        ->lockForUpdate()
                        ->max('numero_licenca') ?? 0;

                    Controle_licencas::create([
                        'estabelecimento_id_cpf' => $id,
                        'divisao_tecnica' => $divisaoTecnica,
                        'ano' => $ano_doc,
                        'numero_licenca' => $ultimaLicenca + 1,
                        'validade' => $data
                    ]);

                    $numeroLicenca = $ultimaLicenca + 1;
                }

        $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_licenca_cpf.docx');

        if($tipo == "licenca"){
            $template->setValue('r', "\u{00A0}\u{00A0}\u{00A0}");
            $template->setValue('l', 'X');
        }elseif($tipo == "renovacao"){
            $template->setValue('r', 'X');
            $template->setValue('l', "\u{00A0}\u{00A0}\u{00A0}");
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', mb_strtoupper($nomeEstabelecimento, 'UTF-8'));
        $template->setValue('numero', $numero);
        $template->setValue('endereco', mb_strtoupper($endereco, 'UTF-8'));
        $template->setValue('bairro',  mb_strtoupper($bairro, 'UTF-8'));
        $template->setValue('atividadePrincipal', mb_strtoupper($atividadePrincipal, 'UTF-8'));
        $template->setValue('numeroLicenca', $numeroLicenca);
        $template->setValue('categoriaLicenca', mb_strtoupper($divisaoTecnica, 'UTF-8'));
        $template->setValue('anoV', $ano + 1);
        $template->setValue('mesV', "MARÇO");

                $template->saveAs(__DIR__ . '/../../../public/storage/documentos/' . $nomeDoc);

                $caminho = storage_path('app/public/documentos/' . $nomeDoc);

                return response()->download($caminho);
                
         }catch(Exception $e){
            return response($e);
         }
    }


/**
 * Display the specified resource.
 */
public function show(string $id)
    {
        
    }

public function requerimento(Request $request, String $id){
    $categoria = $request->categoria;
    $dadosInputs = $request->dados;

    if($categoria == "Profissional Liberal"){
     try{
        $dados = Estabelecimentos_cpf::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco = $dados->endereco;
        $numero = $dados->numero_endereco;
        $atividadePrincipal = $dados->atividade_principal;
        $localidade = $dados->localidade;
        $cpf = $dados->cpf;
        $titulo_profissional = $dados->formacao_profissional;
        $registro_conselho = $dados->registro_conselho;
        $nomeDoc = $nomeEstabelecimento." requerimento.docx";
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');

        $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_requerimento_doc_cpf_pl_of.docx');

        // if($tipo == "licenca"){
        //     $template->setValue('r', ' ');
        //     $template->setValue('l', 'X');
        // }elseif($tipo == "renovacao"){
        //     $template->setValue('r', 'X');
        //     $template->setValue('l', ' ');
        // }

        foreach($dadosInputs as $valor){
            $template->setValue($valor, 'X');
        }

        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
        $template->setValue('atividadePrincipal', $atividadePrincipal);
        $template->setValue('numero', $numero);
        $template->setValue('endereco', $endereco);
        $template->setValue('localidade', $localidade);
        $template->setValue('cpf', $this->cpfFormat($cpf));
        $template->setValue('titulo_profissional', $titulo_profissional);
        $template->setValue('registro_conselho', $registro_conselho);

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

        $pdf = "storage/documentos/". $id ."requerimento.pdf";

        $link = asset($pdf);

        return response()->json([
            'link' => $link
        ]);

            
    }catch(Exception $e){
        return response($e);
    }
    }elseif($categoria == "Autônomo"){
        try{
        $dados = Estabelecimentos_cpf::findOrFail($id);
        $nomeEstabelecimento = $dados->nome_fantasia;
        $endereco = $dados->endereco;
        $numero = $dados->numero_endereco;
        $localidade = $dados->localidade;
        $atividadePrincipal = $dados->atividade_principal;
        $cpf = $dados->cpf;
        $nomeDoc = $id."requerimento.docx";
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');

        $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_requerimento_doc_cpf_au_of.docx');

       foreach($dadosInputs as $valor){
            $template->setValue($valor, 'X');
        }
        
        $template->setValue('dia', $dia);
        $template->setValue('mes', $mes);
        $template->setValue('ano', $ano);
        $template->setValue('nomeEstabelecimento', $nomeEstabelecimento);
        $template->setValue('atividadePrincipal', $atividadePrincipal);
        $template->setValue('numero', $numero);
        $template->setValue('endereco', $endereco);
        $template->setValue('localidade', $localidade);
        $template->setValue('cpf', $this->cpfFormat($cpf));

        $variables = $template->getVariables();
        foreach ($variables as $variable) {
            // Se a variável não foi substituída, ela ainda existe.
            // Define como string vazia para remover o placeholder.
            $template->setValue($variable, '');
        }

        $template->saveAs(__DIR__ . '/../../../public/storage/documentos/' . $nomeDoc);

        $caminho = storage_path('app/public/documentos/' . $nomeDoc);

        $soffice = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';
        $outDir = storage_path('app/public/documentos/');

        $comando = "$soffice --headless --convert-to pdf \"$caminho\" --outdir \"$outDir\"";

        exec($comando);

        $pdf = "storage/documentos/". $id ."requerimento.pdf";

        $link = asset($pdf);

        return response()->json([
            'link' => $link
        ]);

    }catch(Exception $e){
        return response($e);
    }

    }
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, String $id)
    {
        $dados = $request->all();
        try{
            Estabelecimentos_cpf::where('id', $id)
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
    public function destroy(Request $request)
    {
    //     $id = $request->query('id');

    //     $registro = Estabelecimentos_cpf::findOrFail($id);

    //     $registro->delete();

    //    return response()->json([
    //     'message' => 'Excluído com sucesso'
    // ]);

    }
}
