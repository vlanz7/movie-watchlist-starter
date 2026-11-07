<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    protected function prepareForValidation(): void
    {
        $username = $this->input('username');
        $email = $this->input('email');

        $this->merge([
            'username' => is_string($username) ? trim($username) : $username,
            'email'    => is_string($email) ? strtolower(trim($email)) : $email,
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi',
            'username.unique'   => 'Username sudah dipakai',
            'username.max'      => 'Username maksimal 50 karakter',
            'email.required'    => 'Email wajib diisi',
            'email.email'       => 'Format email tidak valid',
            'email.unique'      => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min'      => 'Password minimal 8 karakter',
            'password.max'      => 'Password maksimal 72 karakter',
        ];
    }
}