<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson() && \in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            try {
                json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Malformed JSON: Syntax error',
                ], 400);
            }
        }

        return $next($request);
    }
}
