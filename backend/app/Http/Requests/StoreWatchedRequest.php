<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWatchedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $review = $this->input('review') ?? $this->input('opinion');
        $this->merge([
            'movie_id' => $this->input('movie_id') ?? $this->input('movieId'),
            'review'   => is_string($review) ? trim($review) : $review,
        ]);
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'min:1'],
            'rating'   => ['required', 'integer', 'between:1,5'],
            'review'   => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'movie_id.required' => 'movieId wajib diisi',
            'movie_id.integer'  => 'movieId harus berupa angka',
            'rating.required'   => 'Rating wajib diisi',
            'rating.integer'    => 'Rating harus berupa bilangan bulat',
            'rating.between'    => 'Rating harus antara 1 sampai 5',
            'review.required'   => 'Review wajib diisi',
            'review.max'        => 'Review maksimal 2000 karakter',
        ];
    }
}