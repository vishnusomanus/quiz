<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    protected function sendResponse($data, string $message = '', int $code = 200, bool $status = true): JsonResponse
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function sendError(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $payload = [
            'status'  => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    protected function sendPaginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = '',
        int $code = 200,
        bool $status = true,
    ): JsonResponse {
        $currentPage = $paginator->currentPage();

        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $currentPage,
                'next'         => $paginator->hasMorePages() ? $currentPage + 1 : 0,
                'prev'         => $currentPage > 1 ? $currentPage - 1 : 0,
            ],
        ], $code);
    }
}
