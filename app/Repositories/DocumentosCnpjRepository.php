<?php

namespace App\Repositories;

use App\Models\Documentos_cnpj;
use App\Models\Doc_categoria;
use Illuminate\Support\Collection;

class DocumentosCnpjRepository
{
    // Busca documentos de um estabelecimento CNPJ filtrando por ID e ano.
    public function buscarPorEstabelecimentoEAno(int $estabelecimentoId, int $ano): Collection
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->get();
    }

    // Busca a licença de funcionamento de um estabelecimento CNPJ em um determinado ano.
    public function buscarLicenca(int $estabelecimentoId, int $ano): ?Documentos_cnpj
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->where('nome_doc', 'Licença de Funcionamento')
            ->first();
    }

    // Verifica se já existem documentos cadastrados para o estabelecimento no ano informado.
    public function existeDocumentoNoAno(int $estabelecimentoId, int $ano): bool
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->exists();
    }

    // Verifica se já existe um documento com o mesmo nome para o estabelecimento no ano informado.
    public function existeDocumentoPorNome(int $estabelecimentoId, int $ano, string $nomeDoc): bool
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->where('nome_doc', $nomeDoc)
            ->exists();
    }

    // Retorna o maior ano de documento registrado para o estabelecimento CNPJ.
    public function maxAno(int $estabelecimentoId): ?int
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->max('ano');
    }

    // Lista os tipos de documentos associados a uma categoria de CNPJ.
    public function docsPorCategoria(int $categoriaId): Collection
    {
        return Doc_categoria::where('categoria_id', $categoriaId)
            ->get(['nome_doc', 'fixo']);
    }

    // Busca um documento fixo (herdado do ano anterior) pelo nome no ano especificado.
    public function buscarDocumentoFixo(int $estabelecimentoId, int $ano, string $nomeDoc): ?Documentos_cnpj
    {
        return Documentos_cnpj::select('doc_local', 'data_doc')
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('ano', $ano)
            ->where('nome_doc', $nomeDoc)
            ->first();
    }

    // Cria um novo registro de documento CNPJ.
    public function criar(array $dados): Documentos_cnpj
    {
        return Documentos_cnpj::create($dados);
    }

    // Atualiza os dados de um documento CNPJ pelo id_documento.
    public function atualizarPorIdDocumento(int $idDocumento, array $dados): int
    {
        return Documentos_cnpj::where('id_documento', $idDocumento)->update($dados);
    }

    // Atualiza o status de múltiplos documentos CNPJ em lote.
    public function atualizarStatusEmLote(array $itens): void
    {
        foreach ($itens as $item) {
            Documentos_cnpj::where('id_documento', $item['id_documento'])
                ->update(['status' => $item['status']]);
        }
    }

    // Retorna os anos distintos em que um estabelecimento CNPJ possui documentos, ordenados do mais recente.
    public function anosDistinct(int $estabelecimentoId): Collection
    {
        return Documentos_cnpj::where('estabelecimento_id', $estabelecimentoId)
            ->distinct()
            ->orderByDesc('ano')
            ->pluck('ano');
    }
}