<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstabelecimentoCnpjRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categoria_id'              => 'nullable|integer',
            'tipo_estabelecimento'      => 'nullable|string|max:255',
            'razao_social'              => 'nullable|string|max:255',
            'cnpj'                      => 'nullable|string|size:14',
            'nome_fantasia'             => 'nullable|string|max:255',
            'atividade_principal'       => 'nullable|string|max:255',
            'divisao_tecnica'           => 'nullable|string|in:DCQA,DCSEP,DCDM,DCSHT',
            'insc_estadual'             => 'nullable|string|max:50',
            'insc_municipal'            => 'nullable|string|max:50',
            'cnes'                      => 'nullable|string|max:50',
            'endereco'                  => 'nullable|string|max:255',
            'numero_endereco'           => 'nullable|string|max:20',
            'bairro'                    => 'nullable|string|max:255',
            'localidade'                => 'nullable|string|max:255',
            'municipio'                 => 'nullable|string|max:255',
            'cep'                       => 'nullable|string|size:8',
            'telefone'                  => 'nullable|string|min:10|max:11',
            'email'                     => 'nullable|email|max:255',
            'data_inicio_funcionamento' => 'nullable|date',
            'natureza_juridica'         => 'nullable|string|in:LTDA,MEI,SLU,EI,SA,SS',
            'nome_responsavel'          => 'nullable|string|max:255',
            'cpf'                       => 'nullable|string|size:11',
            'rg'                        => 'nullable|string|max:20',
            'orgao_expedidor'           => 'nullable|string|max:100',
            'data_expedicao_rg'         => 'nullable|date',
            'obs'                       => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.size'                          => 'O CNPJ deve conter 14 dígitos.',
            'divisao_tecnica.in'                 => 'A divisão técnica deve ser DCQA, DCSEP, DCDM ou DCSHT.',
            'cep.size'                           => 'O CEP deve conter 8 dígitos.',
            'telefone.min'                       => 'O telefone deve conter no mínimo 10 dígitos.',
            'email.email'                        => 'Informe um e-mail válido.',
            'data_inicio_funcionamento.date'     => 'Informe uma data válida.',
            'natureza_juridica.in'               => 'A natureza jurídica selecionada é inválida.',
            'cpf.size'                           => 'O CPF deve conter 11 dígitos.',
            'data_expedicao_rg.date'             => 'Informe uma data válida para expedição do RG.',
        ];
    }
}
