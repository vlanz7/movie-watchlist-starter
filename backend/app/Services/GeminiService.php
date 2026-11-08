<?php

namespace App\Services;

use App\Exceptions\GeminiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Lapisan tipis yang hanya mengurus komunikasi dengan Gemini (HTTP). Tidak tahu apa-apa soal film.
class GeminiService
{
    private const TRANSIENT = [500, 502, 503, 504];

    public function generateJson(string $systemInstruction, string $userText, array $schema): ?array
    {
        $apiKey = config('gemini.api_key');
        $models = array_values(array_filter([config('gemini.model'), config('gemini.fallback_model')]));
        if (! $apiKey || $models === []) {
            throw new GeminiException('Layanan AI belum dikonfigurasi', 503);
        }
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $userText]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => $schema,
            ],
        ];
        if ($level = config('gemini.thinking_level')) {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingLevel' => $level];
        }
        $lastStatus = null;
        foreach ($models as $model) {
            $response = $this->send($model, $apiKey, $payload);
            if ($response->successful()) {
                return $this->extractJson($response);
            }
            $lastStatus = $response->status();
            Log::warning('Gemini: respons error', [
                'model'  => $model,
                'status' => $lastStatus,
                'body'   => $response->body(),
            ]);
            if (! in_array($lastStatus, self::TRANSIENT, true)) {
                break;
            }
        }
        throw match (true) {
            $lastStatus === 429 => new GeminiException('Kuota AI sedang habis, coba lagi sebentar lagi', 429),
            $lastStatus >= 500  => new GeminiException('Layanan AI sedang sibuk, coba lagi beberapa saat lagi', 503),
            default             => new GeminiException('Layanan AI sedang bermasalah', 502),
        };
    }

    // private function send(string $model, string $apiKey, array $payload): Response
    // {
    //     $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
    //     try {
    //         return Http::withHeaders(['x-goog-api-key' => $apiKey])
    //             ->acceptJson()
    //             ->asJson()
    //             ->timeout(config('gemini.timeout'))
    //             ->retry(
    //                 3,
    //                 1500,
    //                 fn ($e) => $e instanceof RequestException
    //                     && in_array($e->response->status(), self::TRANSIENT, true),
    //                 throw: false
    //             )
    //             ->post($url, $payload);
    //     } catch (ConnectionException $e) {
    //         Log::warning('Gemini: koneksi gagal / timeout', ['model' => $model, 'error' => $e->getMessage()]);
    //         throw new GeminiException('Layanan AI tidak merespons, coba lagi sebentar lagi', 504);
    //     }
    // }

    private function send(string $model, string $apiKey, array $payload): Response
    {
        $startedAt = microtime(true);
        $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
        try {
            return Http::withOptions([
                'force_ip_resolve' => 'v4',
            ])
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(config('gemini.timeout'))
                ->retry(
                    3,
                    1500,
                    fn ($e) => $e instanceof RequestException
                        && in_array($e->response->status(), self::TRANSIENT, true),
                    throw: false
                )
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Gemini: koneksi gagal / timeout', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw new GeminiException(
                'Layanan AI tidak merespons, coba lagi sebentar lagi',
                504
            );
        }
    }

    private function extractJson(Response $response): ?array
    {
        $text = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($text) || $text === '') {
            Log::warning('Gemini: tidak ada teks pada respons', ['body' => $response->body()]);
            return null;
        }
        $data = json_decode($text, true);
        return is_array($data) ? $data : null;
    }
}