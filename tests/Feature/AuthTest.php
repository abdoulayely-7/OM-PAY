<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $passwordClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client password grant pour les tests
        $this->passwordClient = Client::factory()->create([
            'password_client' => true,
            'revoked' => false,
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token_type',
                    'expires_in',
                    'access_token',
                    'refresh_token',
                ]);
    }

    public function test_user_can_login_with_telephone()
    {
        $user = User::factory()->create([
            'telephone' => '+221771234567',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+221771234567',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token_type',
                    'expires_in',
                    'access_token',
                    'refresh_token',
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();

        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $token->refresh_token,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token_type',
                    'expires_in',
                    'access_token',
                    'refresh_token',
                ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Vérifier que le token a été révoqué
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'id' => $token->id,
            'revoked' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_route()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'email' => $user->email,
                ]);
    }
}
