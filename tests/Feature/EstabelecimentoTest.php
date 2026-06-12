<?php

namespace Tests\Feature;

use Tests\TestCase;

class EstabelecimentoTest extends TestCase
{
    /** @test */
    public function listar_estabelecimentos_cnpj_autenticado()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/estabelecimentos_cnpj');

        $response->assertStatus(200);
    }

    /** @test */
    public function listar_estabelecimentos_cpf_autenticado()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/estabelecimentos_cpf');

        $response->assertStatus(200);
    }

    /** @test */
    public function listar_categorias_cnpj_autenticado()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/getCategorias');

        $response->assertStatus(200);
        $response->assertJsonCount(15);
    }

    /** @test */
    public function listar_categorias_cpf_autenticado()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/getCategorias_cpf');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    /** @test */
    public function requerimento_cnpj_sem_checkboxes_retorna_422()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/gerar_requerimento_cnpj/1');

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Selecione ao menos um item para o requerimento.']);
    }

    /** @test */
    public function requerimento_cpf_sem_checkboxes_retorna_422()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/gerar_requerimento/1', [
            'categoria' => 'Profissional Liberal',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Selecione ao menos um item para o requerimento.']);
    }

    /** @test */
    public function show_cnpj_retorna_405()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/estabelecimentos_cnpj/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function show_cpf_retorna_405()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/estabelecimentos_cpf/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function show_notificados_retorna_405()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/estabelecimentos_notificados/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function deletar_cnpj_nao_autenticado_retorna_401()
    {
        $response = $this->deleteJson('/api/estabelecimentos_cnpj/1');

        $response->assertStatus(401);
    }
}