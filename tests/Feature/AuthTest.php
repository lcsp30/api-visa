<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    /** @test */
    public function login_com_credenciais_validas_retorna_token()
    {
        User::factory()->create([
            'name_user' => 'usuario_teste',
            'password'  => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'name_user' => 'usuario_teste',
            'password'  => 'senha123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
    }

    /** @test */
    public function login_com_credenciais_invalidas_retorna_422()
    {
        $response = $this->postJson('/api/login', [
            'name_user' => 'invalido',
            'password'  => 'errada',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function cadastro_retorna_token()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Novo Usuario',
            'name_user'             => 'novo_user',
            'password'              => 'senha123',
            'password_confirmation' => 'senha123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['user', 'token']);
    }

    /** @test */
    public function acesso_sem_token_retorna_401()
    {
        $response = $this->getJson('/api/estabelecimentos_cnpj');

        $response->assertStatus(401);
    }

    /** @test */
    public function logout_revoga_token()
    {
        $user = User::factory()->create([
            'name_user' => 'logout_test',
            'password'  => bcrypt('password'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'name_user' => 'logout_test',
            'password'  => 'password',
        ]);

        $token = $loginResponse->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logout realizado com sucesso.']);

        // Verifica que o token foi revogado
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/estabelecimentos_cnpj')
            ->assertStatus(401);
    }

    /** @test */
    public function login_com_campos_faltando_retorna_erro()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
    }

    /** @test */
    public function cadastro_com_senhas_diferentes_retorna_erro()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Teste',
            'name_user'             => 'teste_senha',
            'password'              => 'senha123',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertStatus(422);
    }
}