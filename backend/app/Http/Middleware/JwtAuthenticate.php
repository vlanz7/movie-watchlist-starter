<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken(); // membaca "Authorization: Bearer <token>"
        if (! $token) {
            return $this->unauthorized('Token tidak ditemukan');
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (ExpiredException) {
            return $this->unauthorized('Token sudah kedaluwarsa');
        } catch (\Throwable) {
            return $this->unauthorized('Token tidak valid');
        }

        $user = User::find($payload->sub);
        if (! $user) {
            return $this->unauthorized('Pengguna tidak ditemukan');
        }
        $request->setUserResolver(fn () => $user);
        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}