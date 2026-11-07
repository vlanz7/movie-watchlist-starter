<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated(); 
        try {
            $user = User::create([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']), 
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Email atau username sudah terdaftar'], 409);
        }
        return response()->json([
            'message' => 'Registrasi berhasil',
            'token'   => $this->jwt->issue($user),
            'user'    => $this->userPayload($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }
        return response()->json([
            'message'    => 'Login berhasil',
            'token'      => $this->jwt->issue($user),
            'expires_in' => config('jwt.ttl') * 60, // detik
            'user'       => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
        ];
    }
}