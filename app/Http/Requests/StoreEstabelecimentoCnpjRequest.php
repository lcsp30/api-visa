<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstabelecimentoCnpjRequest extends FormRequest
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
            'categoria_id'              => 'required|integer',
            'tipo_estabelecimento'      => 'nullable|string|max:255',
            'razao_social'              => 'required|string|max:255',
            'cnpj'                      => 'required|string|size:14',
            'nome_fantasia'             => 'required|string|max:255',
            'atividade_principal'       => 'required|string|max:255',
            'divisao_tecnica'           => 'required|string|in:DCQA,DCSEP,DCDM,DCSHT',
            'insc_estadual'             => 'nullable|string|max:50',
            'insc_municipal'            => 'nullable|string|max:50',
            'cnes'                      => 'nullable|string|max:50',
            'endereco'                  => 'required|string|max:255',
            'numero_endereco'           => 'required|string|max:20',
            'bairro'                    => 'required|string|max:255',
            'localidade'                => 'nullable|string|max:255',
            'municipio'                 => 'required|string|max:255',
            'cep'                       => 'required|string|size:8',
            'telefone'                  => 'required|string|min:11|max:11',
            'email'                     => 'required|email|max:255',
            'data_inicio_funcionamento' => 'required|date',
            'natureza_juridica'         => 'required|string|in:LTDA,MEI,SLU,EI,SA,SS',
            'nome_responsavel'          => 'required|string|max:255',
            'cpf'                       => 'required|string|size:11',
            'rg'                        => 'required|string|max:20',
            'orgao_expedidor'           => 'required|string|max:100',
            'data_expedicao_rg'         => 'required|date',
            'obs'                       => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'              => 'A categoria é obrigatória.',
            'categoria_id.exists'                => 'A categoria selecionada é inválida.',
            'razao_social.required'              => 'A razão social é obrigatória.',
            'cnpj.required'                      => 'O CNPJ é obrigatório.',
            'cnpj.size'                          => 'O CNPJ deve conter 14 dígitos.',
            'cnpj.unique'                        => 'Este CNPJ já está cadastrado.',
            'nome_fantasia.required'             => 'O nome fantasia é obrigatório.',
            'atividade_principal.required'       => 'A atividade principal é obrigatória.',
            'divisao_tecnica.required'           => 'A divisão técnica é obrigatória.',
            'divisao_tecnica.in'                 => 'A divisão técnica deve ser DCQA, DCSEP, DCDM ou DCSHT.',
            'endereco.required'                  => 'O endereço é obrigatório.',
            'numero_endereco.required'           => 'O número do endereço é obrigatório.',
            'bairro.required'                    => 'O bairro é obrigatório.',
            'municipio.required'                 => 'O município é obrigatório.',
            'cep.required'                       => 'O CEP é obrigatório.',
            'cep.size'                           => 'O CEP deve conter 8 dígitos.',
            'telefone.required'                  => 'O telefone é obrigatório.',
            'telefone.min'                       => 'O telefone deve conter no mínimo 10 dígitos.',
            'email.required'                     => 'O e-mail é obrigatório.',
            'email.email'                        => 'Informe um e-mail válido.',
            'data_inicio_funcionamento.required' => 'A data de início de funcionamento é obrigatória.',
            'data_inicio_funcionamento.date'     => 'Informe uma data válida.',
            'natureza_juridica.required'         => 'A natureza jurídica é obrigatória.',
            'natureza_juridica.in'               => 'A natureza jurídica selecionada é inválida.',
            'nome_responsavel.required'          => 'O nome do responsável é obrigatório.',
            'cpf.required'                       => 'O CPF do responsável é obrigatório.',
            'cpf.size'                           => 'O CPF deve conter 11 dígitos.',
            'rg.required'                        => 'O RG é obrigatório.',
            'orgao_expedidor.required'           => 'O órgão expedidor é obrigatório.',
            'data_expedicao_rg.required'         => 'A data de expedição do RG é obrigatória.',
            'data_expedicao_rg.date'             => 'Informe uma data válida para expedição do RG.',
        ];
    }
}
