<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecommendMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prompt = $this->input('prompt') ?? $this->input('query') ?? $this->input('description');
        $this->merge([
            'prompt' => is_string($prompt) ? trim($prompt) : $prompt,
        ]);
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Deskripsi atau mood film wajib diisi',
            'prompt.min'      => 'Deskripsi terlalu pendek (minimal 3 karakter)',
            'prompt.max'      => 'Deskripsi terlalu panjang (maksimal 500 karakter)',
        ];
    }
}
