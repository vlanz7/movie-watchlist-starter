<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

class JwtService
{
    private const ALGORITHM = 'HS256';
    public function issue(User $user): string
    {
        $now = time();
        return JWT::encode([
            'iss' => config('app.url'),
            'sub' => $user->id,                          // siapa pemilik token
            'iat' => $now,                               // dibuat kapan
            'exp' => $now + config('jwt.ttl') * 60,      // kedaluwarsa kapan
        ], $this->secret(), self::ALGORITHM);
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret(), self::ALGORITHM));
    }

    private function secret(): string
    {
        $secret = config('jwt.secret');
        if (! $secret) {
            throw new RuntimeException('JWT_SECRET belum diatur di file .env');
        }
        return $secret;
    }
}