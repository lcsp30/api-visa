<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentosTest extends TestCase
{
    /** @test */
    public function listar_documentos_sem_parametros_retorna_erro()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/doc');

        $response->assertStatus(200);
        $response->assertJson(['Erro' => 'Dados faltando da Requisição!!']);
    }

    /** @test */
    public function listar_licencas_autenticado()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/getLicencas');

        $response->assertStatus(200);
        $response->assertJsonStructure(['DCQA', 'DCSEP', 'DCDM', 'DCSHT']);
    }
}