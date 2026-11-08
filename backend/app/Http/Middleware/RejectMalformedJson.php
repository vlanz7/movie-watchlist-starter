<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class RejectMalformedJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->getContent();
        if ($request->isJson() && $content !== '') {
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Format JSON tidak valid'], 400);
            }
        }
        return $next($request);
    }
}