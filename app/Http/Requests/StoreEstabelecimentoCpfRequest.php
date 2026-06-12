<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstabelecimentoCpfRequest extends FormRequest
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
            'categoria_id'          => 'required|integer',
            'tipo_estabelecimento'  => 'nullable|string|max:255',
            'nome'                  => 'required|string|max:255',
            'cpf'                   => 'required|string|size:11',
            'rg'                    => 'required|string|max:20',
            'orgao_expedidor'       => 'required|string|max:100',
            'data_expedicao_rg'     => 'required|date',
            'escolaridade'          => 'nullable|string|max:255',
            'formacao_profissional' => 'nullable|string|max:255',
            'registro_conselho'     => 'nullable|string|max:255',
            'especializacao'        => 'nullable|string|max:255',
            'nome_fantasia'         => 'required|string|max:255',
            'atividade_principal'   => 'required|string|max:255',
            'divisao_tecnica'       => 'required|string|in:DCQA,DCSEP,DCDM,DCSHT',
            'data_inicio_funcionamento' => 'required|date',
            'endereco'              => 'required|string|max:255',
            'localidade'            => 'nullable|string|max:255',
            'numero_endereco'       => 'required|string|max:20',
            'bairro'                => 'required|string|max:255',
            'complemento_endereco'  => 'nullable|string|max:255',
            'municipio'             => 'required|string|max:255',
            'cep'                   => 'required|string|size:8',
            'telefone'              => 'required|string|min:11|max:11',
            'email'                 => 'required|email|max:255',
            'obs'                   => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'              => 'A categoria é obrigatória.',
            'categoria_id.exists'                => 'A categoria selecionada é inválida.',
            'nome.required'                      => 'O nome é obrigatório.',
            'cpf.required'                       => 'O CPF é obrigatório.',
            'cpf.size'                           => 'O CPF deve conter 11 dígitos.',
            'cpf.unique'                         => 'Este CPF já está cadastrado.',
            'rg.required'                        => 'O RG é obrigatório.',
            'orgao_expedidor.required'           => 'O órgão expedidor é obrigatório.',
            'data_expedicao_rg.required'         => 'A data de expedição do RG é obrigatória.',
            'data_expedicao_rg.date'             => 'Informe uma data válida para expedição do RG.',
            'nome_fantasia.required'             => 'O nome fantasia é obrigatório.',
            'atividade_principal.required'       => 'A atividade principal é obrigatória.',
            'divisao_tecnica.required'           => 'A divisão técnica é obrigatória.',
            'divisao_tecnica.in'                 => 'A divisão técnica deve ser DCQA, DCSEP, DCDM ou DCSHT.',
            'endereco.required'                  => 'O endereço é obrigatório.',
            'numero_endereco.required'           => 'O número do endereço é obrigatório.',
            'bairro.required'                    => 'O bairro é obrigatório.',
            'municipio.required'                 => 'O município é obrigatório.',
            'uf.required'                        => 'A UF é obrigatória.',
            'uf.size'                            => 'A UF deve conter 2 caracteres.',
            'cep.required'                       => 'O CEP é obrigatório.',
            'cep.size'                           => 'O CEP deve conter 8 dígitos.',
            'telefone.required'                  => 'O telefone é obrigatório.',
            'telefone.min'                       => 'O telefone deve conter no mínimo 11 dígitos.',
            'email.required'                     => 'O e-mail é obrigatório.',
            'email.email'                        => 'Informe um e-mail válido.',
            'data_inicio_funcionamento.required' => 'A data de início de funcionamento é obrigatória.',
            'data_inicio_funcionamento.date'     => 'Informe uma data válida.',
        ];
    }
}
