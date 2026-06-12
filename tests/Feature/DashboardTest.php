<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    /** @test */
    public function metricas_retorna_estrutura_correta()
    {
        $this->authenticateAsAdmin();

        $response = $this->getJson('/api/metricas');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totalCadastrados',
            'licencasAtivas',
            'licencasVencidas',
            'notificados',
        ]);
    }
}