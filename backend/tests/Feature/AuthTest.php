<?php

namespace Tests\Feature;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Tests\ApiTestCase;

class AuthTest extends ApiTestCase
{
    private function payload(array $override = []): array
    {
        return array_merge([
            'username' => 'keisha',
            'email'    => 'keisha@example.com',
            'password' => 'password123',
        ], $override);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $this->postJson('/api/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'username', 'email']])
            ->assertJsonMissingPath('user.password');
        $this->assertDatabaseHas('users', ['email' => 'keisha@example.com', 'username' => 'keisha']);
    }

    public function test_password_is_hashed_in_database(): void
    {
        $this->postJson('/api/auth/register', $this->payload());
        $user = User::where('email', 'keisha@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $this->postJson('/api/auth/register', $this->payload(['email' => '  KEISHA@Example.COM ']))
            ->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'keisha@example.com']);
    }

    public function test_register_rejects_duplicate_email_even_with_different_case(): void
    {
        $this->postJson('/api/auth/register', $this->payload())->assertCreated();
        $this->postJson('/api/auth/register', $this->payload(['username' => 'lain', 'email' => 'KEISHA@EXAMPLE.COM']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('message', 'Email sudah terdaftar');
    }

    public function test_register_rejects_duplicate_username(): void
    {
        $this->postJson('/api/auth/register', $this->payload())->assertCreated();
        $this->postJson('/api/auth/register', $this->payload(['email' => 'lain@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    public function test_register_validates_required_fields(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email', 'password']);
    }

    public function test_register_rejects_short_password_and_bad_email(): void
    {
        $this->postJson('/api/auth/register', $this->payload(['password' => 'short', 'email' => 'bukan-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'email']);
    }

    public function test_validation_errors_are_json_even_without_accept_header(): void
    {
        $this->postJson('/api/auth/register', [], ['Accept' => 'text/html'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_malformed_json_body_returns_400(): void
    {
        $this->call(
            'POST',
            '/api/auth/login',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{"email": "a@b.com", "password": '
        )
            ->assertStatus(400)
            ->assertJsonPath('message', 'Format JSON tidak valid');
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $this->makeUser(['email' => 'keisha@example.com']);
        $this->postJson('/api/auth/login', ['email' => 'keisha@example.com', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'username', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser(['email' => 'keisha@example.com']);
        $this->postJson('/api/auth/login', ['email' => 'keisha@example.com', 'password' => 'salah-salah'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah');
    }

    public function test_login_gives_same_message_for_unknown_email(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'tidakada@example.com', 'password' => 'password123'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token tidak ditemukan');
    }

    public function test_protected_route_accepts_valid_token(): void
    {
        $user = $this->makeUser();
        $this->getJson('/api/auth/me', $this->bearer($user))
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_token_from_register_can_be_used_immediately(): void
    {
        $token = $this->postJson('/api/auth/register', $this->payload())->json('token');
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('user.email', 'keisha@example.com');
    }

    public function test_malformed_token_is_rejected(): void
    {
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer bukan-jwt'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token tidak valid');
    }

    public function test_token_signed_with_another_secret_is_rejected(): void
    {
        $user = $this->makeUser();
        $forged = JWT::encode(
            ['sub' => $user->id, 'iat' => time(), 'exp' => time() + 600],
            str_repeat('z', 48),
            'HS256'
        );
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$forged}"])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token tidak valid');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->makeUser();
        $expired = JWT::encode(
            ['sub' => $user->id, 'iat' => time() - 3600, 'exp' => time() - 60],
            config('jwt.secret'),
            'HS256'
        );
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$expired}"])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token sudah kedaluwarsa');
    }

    public function test_token_of_deleted_user_is_rejected(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($user);
        $user->delete();
        $this->getJson('/api/auth/me', $headers)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Pengguna tidak ditemukan');
    }
}