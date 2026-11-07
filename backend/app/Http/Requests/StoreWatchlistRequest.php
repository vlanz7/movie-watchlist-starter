<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation(): void
    {
        $this->merge([
            'movie_id' => $this->input('movie_id') ?? $this->input('movieId'),
        ]);
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'movie_id.required' => 'movieId wajib diisi',
            'movie_id.integer'  => 'movieId harus berupa angka',
            'movie_id.min'      => 'movieId tidak valid',
        ];
    }
}