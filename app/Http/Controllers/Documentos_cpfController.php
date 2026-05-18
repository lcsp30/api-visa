<?php

namespace App\Http\Controllers;

use App\Models\Controle_licencas;
use App\Models\Documentos_cnpj;
use App\Models\Documentos_cpf;
use App\Models\Estabelecimentos_cnpj;
use App\Models\Estabelecimentos_cpf;
use App\Models\Intimacao_constatacao;
use App\Models\Intimacao_constatacao_cpf;
use App\Models\Doc_categoria;
use App\Models\Doc_categoria_cpf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class Documentos_cpfController extends Controller
{

    public function formatCNPJ($cnpj)
    {
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
    public function index(Request $request)
    {
        $id = $request->id;
        $ano = $request->ano;
        $tipo = $request->tipo;

        if ($id && $ano && $tipo) {
            if ($tipo == "cnpj") {

                try {
                    $todosDados = Documentos_cnpj::where('estabelecimento_id', $id)
                        ->where('ano', $ano)
                        ->get();

                    $licenca = $todosDados->where('nome_doc', 'Licença de Funcionamento')->first();

                    $dados = $todosDados->reject(fn($item) => $item->nome_doc === 'Licença de Funcionamento');

                    $dadosAtivos = $dados->where('status', !1)->values()->all();

                    $docPendentes = $dados->where('doc_local', null)->values()->all();
                    $docAnexados = $dados->where('doc_local', !null)->values()->all();

                    $dadosAuxiliares = Estabelecimentos_cnpj::query()
                    ->select('id', 'situacao')
                    ->withMax('documento', 'ano')
                    ->with(['intimacoes' => function($query) use ($ano){
                        $query->select('id_intimacao_constatacao','estabelecimento_id','tipo', 'descricao', 'data_inicial', 'data_expiracao', 'status')
                        ->where('ano', $ano);
                    }])
                    ->findOrFail($id);

                    $maoirAno = $dadosAuxiliares->documento_max_ano;
                    $situacao = $dadosAuxiliares->situacao;
                    $dadosAgrupados = $dadosAuxiliares->intimacoes->groupBy('tipo');

                    if ($ano == $maoirAno) {
                        if (is_null($licenca->doc_local) && $situacao != 0) {
                            Estabelecimentos_cnpj::where('id', $id)
                                ->update([
                                    'situacao' => 0
                                ]);
                        } elseif (!is_null($licenca->doc_local) && $situacao != 1) {
                            Estabelecimentos_cnpj::where('id', $id)
                                ->update([
                                    'situacao' => 1
                                ]);
                        }
                    }



                    // $dadosAgrupados = $dados2->groupBy('tipo');

                    return response()->json([
                        'doc' => $dados,
                        'docAtivos' => $dadosAtivos,
                        'docPendentes' => $docPendentes,
                        'docAnexados' => $docAnexados,
                        'licenca' => $licenca,
                        'intimacao' => $dadosAgrupados[1] ?? collect(),
                        'constatacao' => $dadosAgrupados[2] ?? collect(),
                        'teste' => $maoirAno
                    ], 200);
                } catch (Exception $e) {

                    return response($e);
                }
            } elseif ($tipo == "cpf") {
                try {
                    $todosDados = Documentos_cpf::where('estabelecimento_id', $id)
                        ->where('ano', $ano)
                        ->get();

                    $licenca = $todosDados->where('nome_doc', 'Licença de Funcionamento')->first();

                    $dados = $todosDados->reject(fn($item) => $item->nome_doc === 'Licença de Funcionamento');

                    $dadosAtivos = $dados->where('status', !1)->values()->all();

                    $docPendentes = $dados->where('doc_local', null)->values()->all();
                    $docAnexados = $dados->where('doc_local', !null)->values()->all();

                  $dadosAuxiliares = Estabelecimentos_cpf::query()
                    ->select('id', 'situacao')
                    ->withMax('documentos', 'ano')
                    ->with(['intimacoes' => function($query) use ($ano){
                        $query->select('id_intimacao_constatacao','estabelecimento_id','tipo', 'descricao', 'data_inicial', 'data_expiracao', 'status')
                        ->where('ano', $ano);
                    }])
                    ->findOrFail($id);

                    $maoirAno = $dadosAuxiliares->documentos_max_ano;
                    $situacao = $dadosAuxiliares->situacao;
                    $dadosAgrupados = $dadosAuxiliares->intimacoes->groupBy('tipo');

                    if ($ano == $maoirAno) {
                        if (is_null($licenca->doc_local) && $situacao != 0) {
                            Estabelecimentos_cpf::where('id', $id)
                                ->update([
                                    'situacao' => 0
                                ]);
                        } elseif (!is_null($licenca->doc_local) && $situacao != 1) {
                            Estabelecimentos_cpf::where('id', $id)
                                ->update([
                                    'situacao' => 1
                                ]);
                        }
                    }

                    return response()->json([
                        'doc' => $dados,
                        'docAtivos' => $dadosAtivos,
                        'docPendentes' => $docPendentes,
                        'docAnexados' => $docAnexados,
                        'licenca' => $licenca,
                        'intimacao' => $dadosAgrupados[1] ?? collect(),
                        'constatacao' => $dadosAgrupados[2] ?? collect(),
                        'teste' => $maoirAno
                    ], 200);
                } catch (Exception $e) {
                    return response($e);
                }
            }
        } else {
            return response()->json([
                'Erro' => "Dados faltando da Requisição!!"
            ]);
        }
    }

    public function getLicencas()
    {
        try {

            $dados = DB::table('controle_licencas')
                ->whereRaw('controle_licencas.ano = (
        SELECT MAX(ano) FROM controle_licencas AS sub 
        WHERE 
            (sub.estabelecimento_id_cnpj = controle_licencas.estabelecimento_id_cnpj 
                OR (sub.estabelecimento_id_cnpj IS NULL AND controle_licencas.estabelecimento_id_cnpj IS NULL))
            AND 
            (sub.estabelecimento_id_cpf = controle_licencas.estabelecimento_id_cpf 
                OR (sub.estabelecimento_id_cpf IS NULL AND controle_licencas.estabelecimento_id_cpf IS NULL))
    )')
                ->leftJoin('estabelecimentos_cnpj', 'controle_licencas.estabelecimento_id_cnpj', '=', 'estabelecimentos_cnpj.id')
                ->leftJoin('estabelecimentos_cpf', 'controle_licencas.estabelecimento_id_cpf', '=', 'estabelecimentos_cpf.id')
                ->leftJoin('categorias', 'categorias.id_categoria', '=', 'estabelecimentos_cnpj.categoria_id')
                ->leftJoin('categorias_cpf', 'categorias_cpf.id_categoria', '=', 'estabelecimentos_cpf.categoria_id')
                ->select(
                    'controle_licencas.numero_licenca',
                    'estabelecimentos_cnpj.cnpj',
                    'estabelecimentos_cpf.cpf',
                    'estabelecimentos_cnpj.nome_fantasia as nome_fantasia_cnpj',
                    'estabelecimentos_cpf.nome_fantasia',
                    'categorias.nome_categoria as categoria_cnpj',
                    'estabelecimentos_cnpj.tipo_estabelecimento as tipo_estb_cnpj',
                    'categorias_cpf.nome_categoria as categoria_cpf',
                    'estabelecimentos_cpf.tipo_estabelecimento as tipo_estb_cpf',
                    'estabelecimentos_cnpj.categoria_id as categoria_id_cnpj',
                    'estabelecimentos_cpf.categoria_id as categoria_id_cpf',
                    'controle_licencas.ano',
                    'estabelecimentos_cnpj.divisao_tecnica',
                    'estabelecimentos_cpf.divisao_tecnica as divisao_tecnica_cpf'
                )
                ->get();

            $dcqa = $dados->filter(function ($item) {
                    return $item->divisao_tecnica === 'DCQA'
                        || $item->divisao_tecnica_cpf === 'DCQA';
                })->values();
            $dcsep = $dados->filter(function ($item) {
                    return $item->divisao_tecnica === 'DCSEP'
                        || $item->divisao_tecnica_cpf === 'DCSEP';
                })->values();
            $dcdm = $dados->filter(function ($item) {
                    return $item->divisao_tecnica === 'DCDM'
                        || $item->divisao_tecnica_cpf === 'DCDM';
                })->values();
            $dcsht = $dados->filter(function ($item) {
                    return $item->divisao_tecnica === 'DCSHT'
                        || $item->divisao_tecnica_cpf === 'DCSHT';
                })->values();

            return response()->json([
                'DCQA' => $dcqa,
                'DCSEP' => $dcsep,
                'DCDM' => $dcdm,
                'DCSHT' => $dcsht
            ]);
        } catch (Exception $e) {
            return response($e);
        }
    }

    public function criarDocsNovoAno(Request $request)
    {
        $id = $request->id;
        $ano = $request->ano;
        $id_categoria = $request->id_categoria;
        $tipo = $request->tipo;

        if ($tipo == "cnpj") {
            $anos = Documentos_cnpj::where('estabelecimento_id', $id)
                ->where('ano', $ano)
                ->exists();

            if ($anos) {
                return response()->json([
                    'res' => "O Estabelecimento já possui Documentos no ANO adicionado!"
                ]);
            }

            try {
                $maoirAno = Documentos_cnpj::where('estabelecimento_id', $id)
                    ->max('ano');

                $doc_categoria = Doc_categoria::where('categoria_id', $id_categoria)
                    ->get(['nome_doc', 'fixo']);

                foreach ($doc_categoria as $d) {

                    if ($d->fixo == 1) {
                        $infoDoc = Documentos_cnpj::select('doc_local', 'data_doc')
                            ->where('estabelecimento_id', $id)
                            ->where('ano', $maoirAno)
                            ->where('nome_doc', $d->nome_doc)
                            ->first();

                        Documentos_cnpj::create([
                            'estabelecimento_id' => $id,
                            'ano' => $ano,
                            'nome_doc' => $d->nome_doc,
                            'doc_local' => $infoDoc->doc_local,
                            'doc_fixo' => $d->fixo,
                            'data_doc' => $infoDoc->data_doc
                        ]);
                    } else {
                        Documentos_cnpj::create([
                            'estabelecimento_id' => $id,
                            'ano' => $ano,
                            'nome_doc' => $d->nome_doc,
                            'doc_local' => null,
                            'doc_fixo' => $d->fixo,
                            'data_doc' => null
                        ]);
                    }
                }

                return response()->json([
                    'res' => "Documentos do Ano de $ano criados!"
                ]);
            } catch (Exception $e) {
                return response($e);
            }
        } elseif ($tipo == "cpf") {

            $anos = Documentos_cpf::where('estabelecimento_id', $id)
                ->where('ano', $ano)
                ->exists();

            if ($anos) {
                return response()->json([
                    'res' => "O Estabelecimento já possui Documentos no ANO adicionado!"
                ]);
            }

            try {

                $maoirAno = Documentos_cpf::where('estabelecimento_id', $id)
                    ->max('ano');

                $doc_categoria = Doc_categoria_cpf::where('categoria_id', $id_categoria)
                    ->get(['nome_doc', 'fixo']);

                foreach ($doc_categoria as $d) {

                    if ($d->fixo == 1) {
                        $infoDoc = Documentos_cpf::select('doc_local', 'data_doc')
                            ->where('estabelecimento_id', $id)
                            ->where('ano', $maoirAno)
                            ->where('nome_doc', $d->nome_doc)
                            ->first();

                        Documentos_cpf::create([
                            'estabelecimento_id' => $id,
                            'ano' => $ano,
                            'nome_doc' => $d->nome_doc,
                            'doc_local' => $infoDoc->doc_local,
                            'doc_fixo' => $d->fixo,
                            'data_doc' => $infoDoc->data_doc
                        ]);
                    } else {

                        Documentos_cpf::create([
                            'estabelecimento_id' => $id,
                            'ano' => $ano,
                            'nome_doc' => $d->nome_doc,
                            'doc_local' => null,
                            'doc_fixo' => $d->fixo,
                            'data_doc' => null
                        ]);
                    }
                }

                return response()->json([
                    'res' => "Documentos do Ano de $ano criados!"
                ]);
            } catch (Exception $e) {
                return response($e);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('arquivo')) {
            $arquivo = $request->file('arquivo')->store('documentos', 'public');
            $id = $request->id;
            $tipo = $request->tipo;


            if ($tipo == "cnpj") {
                try {
                    Documentos_cnpj::where('id_documento', $id)
                        ->update(
                            [
                                'doc_local' => $arquivo,
                                'data_doc' => date('Y-m-d'),
                                'url' => asset('storage/' . $arquivo)
                            ]
                        );



                    return response()->json([
                        'Sucesso' => 'Arquivo adicionado com Sucesso!!'
                    ], 200);
                } catch (Exception $e) {
                    return response()->json([
                        'Error' => 'Erro ao adicionar Arquivo!!'
                    ]);
                }
            } else if ($tipo == "cpf") {
                try {
                    Documentos_cpf::where('id_documento', $id)
                        ->update([
                            'doc_local' => $arquivo,
                            'data_doc' => date('Y-m-d'),
                            'url' => asset('storage/' . $arquivo)
                        ]);

                    return response()->json([
                        'Sucesso' => 'Arquivo adicionado com Sucesso!!'
                    ], 200);
                } catch (Exception $e) {
                    return response($e);
                }
            }
        }
    }

    public function storeIntimacaoConstatacao(Request $request)
    {
        $id = $request->id;
        $estabelecimento_id = $request->estabelecimento_id;
        $ano = $request->ano;
        $tipo = $request->tipo;
        $tipoEstabelecimento = $request->tipoEstabelecimento;
        $descricao = $request->descricao;
        $dataExpiracao = $request->data_expiracao;
        $finalizar = $request->finalizar;

        if ($tipoEstabelecimento == "cnpj") {
            if ($finalizar == 1) {
                try {
                    Intimacao_constatacao::updateOrCreate(
                        [
                            'id_intimacao_constatacao' => $id
                        ],
                        [
                            'status' => false,
                        ]
                    );

                    return response()->json([
                        'Menssagem' => "Finalizado com sucesso!"
                    ], 201);
                } catch (Exception $e) {
                    return response($e);
                }
            } else {
                try {
                    Intimacao_constatacao::updateOrCreate(
                        [
                            'estabelecimento_id' => $estabelecimento_id,
                            'ano' => $ano,
                            'tipo' => $tipo
                        ],
                        [
                            'estabelecimento_id' => $estabelecimento_id,
                            'ano' => $ano,
                            'status' => true,
                            'tipo' => $tipo,
                            'descricao' => $descricao,
                            'data_inicial' => date('Y-m-d'),
                            'data_expiracao' => $dataExpiracao
                        ]
                    );

                    return response()->json([
                        'Menssagem' => "Criado com sucesso!"
                    ], 201);
                } catch (Exception $e) {
                    return response($e);
                }
            }
        } elseif ($tipoEstabelecimento == "cpf") {

            if ($finalizar == 1) {
                try {
                    Intimacao_constatacao_cpf::updateOrCreate(
                        [
                            'id_intimacao_constatacao' => $id
                        ],
                        [
                            'status' => false,
                        ]
                    );

                    return response()->json([
                        'Menssagem' => "Finalizado com sucesso!"
                    ], 201);
                } catch (Exception $e) {
                    return response($e);
                }
            } else {
                try {
                    Intimacao_constatacao_cpf::updateOrCreate(
                        [
                            'estabelecimento_id' => $estabelecimento_id,
                            'ano' => $ano,
                            'tipo' => $tipo
                        ],
                        [
                            'estabelecimento_id' => $estabelecimento_id,
                            'ano' => $ano,
                            'status' => true,
                            'tipo' => $tipo,
                            'descricao' => $descricao,
                            'data_inicial' => date('Y-m-d'),
                            'data_expiracao' => $dataExpiracao
                        ]
                    );

                    return response()->json([
                        'Menssagem' => "Criado com sucesso!"
                    ], 201);
                } catch (Exception $e) {
                    return response($e);
                }
            }
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $id = $request->query('id');
        $tipo = $request->tipo;

        try {
            if ($tipo == "cpf") {
                if ($id) {
                    $dados = Documentos_cpf::where('estabelecimento_id', $id)
                        ->distinct()
                        ->orderByDesc('ano')
                        ->pluck('ano');

                    return response()->json($dados, 201);
                } else {
                    return response()->json([
                        'Menssagem' => 'Erro ao Buscar Dados!!'
                    ]);
                }
            } elseif ($tipo == "cnpj") {

                if ($id) {
                    $dados = Documentos_cnpj::where('estabelecimento_id', $id)
                        ->distinct()
                        ->orderByDesc('ano')
                        ->pluck('ano');

                    return response()->json($dados, 201);
                } else {
                    return response()->json([
                        'Menssagem' => 'Erro ao Buscar Dados!!'
                    ]);
                }
            }
        } catch (Exception $e) {
            return response($e);
        }
    }

    public function download(Request $request)
    {
        $docUrl = $request->url;
        $caminho = storage_path("app/public/" . $docUrl);

        try {
            return response()->download($caminho);
        } catch (Exception $e) {
            return response()->json([
                'Error' => 'Erro ao fazer Download!!!',
            ]);
        }
    }

    public function gerarProtocolo(Request $request, String $id)
    {
        // $validade = $request->validade;
        $n = $request->n;
        $especificos = $request->especificos;
        $tipo = $request->tipo;
        $validadeParts = explode('-', $request->validade);

        if ($tipo == "cnpj") {

            try {
                $dados = Estabelecimentos_cnpj::select('nome_responsavel', 'endereco', 'numero_endereco', 'localidade', 'cnpj', 'atividade_principal')
                    ->findOrFail($id);

                $nomeResponsavel = $dados->nome_responsavel;
                $endereco = $dados->endereco;
                $numero = $dados->numero_endereco;
                $localidade = $dados->localidade;
                $cnpj = $dados->cnpj;
                $atividadePrincipal = $dados->atividade_principal;
                $ano = date('Y');
                $mes = date('m');
                $dia = date('d');
                $vAno = $validadeParts[0];
                $vmes = $validadeParts[1];
                $vDia = $validadeParts[2];
                $nomeDoc = $id . "protocolo.docx";

                $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_protocolo.docx');

                foreach ($especificos as $valor) {
                    $template->setValue($valor, 'X');
                }

                $template->setValue('nomeResponsavel', $nomeResponsavel);
                $template->setValue('endereco', $endereco);
                $template->setValue('numero', $numero);
                $template->setValue('localidade', $localidade);
                $template->setValue('cnpj', $this->formatCNPJ($cnpj));
                $template->setValue('atividadePrincipal', $atividadePrincipal);
                $template->setValue('ano', $ano);
                $template->setValue('mes', $mes);
                $template->setValue('dia', $dia);
                $template->setValue('av', $vAno);
                $template->setValue('mv', $vmes);
                $template->setValue('dv', $vDia);
                $template->setValue('n', $n);

                $variables = $template->getVariables();
                foreach ($variables as $variable) {
                    // Se a variável não foi substituída, ela ainda existe.
                    // Define como string vazia para remover o placeholder.
                    $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
                }

                $template->saveAs(__DIR__ . '/../../../public/storage/documentos/protocolos/' . $nomeDoc);

                $caminho = storage_path('app/public/documentos/protocolos/' . $nomeDoc);

                return response()->download($caminho);
            } catch (Exception $e) {
                return response($e);
            }
        } elseif ($tipo == "cpf") {

            try {
                $dados = Estabelecimentos_cpf::select('nome', 'endereco', 'numero_endereco', 'localidade', 'atividade_principal')
                    ->findOrFail($id);

                $nomeResponsavel = $dados->nome;
                $endereco = $dados->endereco;
                $numero = $dados->numero_endereco;
                $localidade = $dados->localidade;
                $cnpj = $dados->cnpj;
                $atividadePrincipal = $dados->atividade_principal;
                $ano = date('Y');
                $mes = date('m');
                $dia = date('d');
                $vAno = $validadeParts[0];
                $vmes = $validadeParts[1];
                $vDia = $validadeParts[2];
                $nomeDoc = $id . "protocolo.docx";

                $template = new TemplateProcessor(__DIR__ . '/../../../public/storage/templates/templete_protocolo.docx');

                foreach ($especificos as $valor) {
                    $template->setValue($valor, 'X');
                }

                $template->setValue('nomeResponsavel', $nomeResponsavel);
                $template->setValue('endereco', $endereco);
                $template->setValue('numero', $numero);
                $template->setValue('localidade', $localidade);
                $template->setValue('cnpj', "XXXXXXXXXXXXX");
                $template->setValue('atividadePrincipal', $atividadePrincipal);
                $template->setValue('ano', $ano);
                $template->setValue('mes', $mes);
                $template->setValue('dia', $dia);
                $template->setValue('av', $vAno);
                $template->setValue('mv', $vmes);
                $template->setValue('dv', $vDia);
                $template->setValue('n', $n);

                $variables = $template->getVariables();
                foreach ($variables as $variable) {
                    // Se a variável não foi substituída, ela ainda existe.
                    // Define como string vazia para remover o placeholder.
                    $template->setValue($variable, "\u{00A0}\u{00A0}\u{00A0}");
                }

                $template->saveAs(__DIR__ . '/../../../public/storage/documentos/protocolos/' . $nomeDoc);

                $caminho = storage_path('app/public/documentos/protocolos/' . $nomeDoc);

                return response()->download($caminho);
            } catch (Exception $e) {
                return response($e);
            }
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $dados = $request->dados;
        $tipo = $request->tipo;

        if ($tipo == 'cpf') {
            try {
                foreach ($dados as $item) {
                    Documentos_cpf::where('id_documento', $item['id_documento'])
                        ->update(['status' => $item['status']]);
                };

                return response()->json([
                    "Menssagem" => "Alterado com Sucesso!! $tipo"
                ], 200);
            } catch (Exception $e) {
                return response($e);
            }
        } elseif ($tipo == 'cnpj') {
            try {
                foreach ($dados as $item) {
                    Documentos_cnpj::where('id_documento', $item['id_documento'])
                        ->update(['status' => $item['status']]);
                };

                return response()->json([
                    "Menssagem" => "Alterado com Sucesso!!"
                ], 200);
            } catch (Exception $e) {
                return response($e);
            }
        } else {
            return response()->json([
                "Menssagem" => "Tipo não encontrado!!" . $tipo
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        $caminho = $request->url;
        $tipo = $request->tipo;

        if ($tipo == "cnpj") {
            try {
                Documentos_cnpj::where('id_documento', $id)->update(
                    [
                        'doc_local' => null,
                        'data_doc' => null,
                        'url' => null
                    ]
                );

                $docApagado = Storage::disk('public')->delete($caminho);

                return response()->json([
                    'Menssagem' => 'Documento apagado com Sucessso!!',
                    'Doc' => $docApagado,
                    'Caminho' => $caminho
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'Menssagem' => 'Falha ao Apagar'
                ], 400);
            }
        } elseif ($tipo == "cpf") {
            try {
                Documentos_cpf::where('id_documento', $id)->update(
                    [
                        'doc_local' => null,
                        'data_doc' => null,
                        'url' => null
                    ]
                );

                $docApagado = Storage::disk('public')->delete($caminho);

                return response()->json([
                    'Menssagem' => 'Documento apagado com Sucessso!!',
                    'Doc' => $docApagado,
                    'Caminho' => $caminho
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'Menssagem' => 'Falha ao Apagar'
                ], 400);
            }
        }
    }
}
