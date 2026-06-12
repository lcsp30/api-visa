<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Executa migrations e seeders em memória (SQLite) para cada teste
        $this->artisan('migrate:fresh');
        $this->seed();
    }

    /**
     * Autentica um usuário como admin e retorna-o com token.
     */
    protected function authenticateAsAdmin()
    {
        $user = \App\Models\User::factory()->create([
            'name_user'    => 'admin_test',
            'password'     => bcrypt('password'),
            'nivel_acesso' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    /**
     * Autentica um usuário como técnico.
     */
    protected function authenticateAsTecnico()
    {
        $user = \App\Models\User::factory()->create([
            'name_user'    => 'tecnico_test',
            'password'     => bcrypt('password'),
            'nivel_acesso' => 2,
        ]);

        Sanctum::actingAs($user, ['*']);

        return $user;
    }
}