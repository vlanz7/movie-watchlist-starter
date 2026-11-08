<?php

namespace Tests;

use App\Models\User;
use App\Services\JwtService;
use Database\Seeders\MovieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database   = (string) config("database.connections.{$connection}.database");
        $safe = ($connection === 'sqlite' && $database === ':memory:')
            || str_ends_with($database, '_test');
        if (! $safe) {
            throw new RuntimeException(
                "Test dihentikan demi keamanan: koneksi '{$connection}' / database '{$database}' bukan database test. "
                . "Atur DB_CONNECTION=sqlite dan DB_DATABASE=:memory: di phpunit.xml."
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'jwt.secret'            => str_repeat('t', 48),
            'jwt.ttl'               => 60,
            'gemini.api_key'        => 'test-key',
            'gemini.model'          => 'test-model',
            'gemini.fallback_model' => null,
            'gemini.thinking_level' => 'low',
        ]);
        Sleep::fake(); 
    }

    protected function seedMovies(): void
    {
        $this->seed(MovieSeeder::class); 
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'username' => 'user_' . Str::lower(Str::random(6)),
            'email'    => Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    protected function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer ' . app(JwtService::class)->issue($user)];
    }

    protected function geminiBody(array $data): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode($data)]]]]]];
    }
}