<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

trait ApiResponder
{
    protected function successResponse($data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'errors' => null,
            'meta' => array_merge(['request_id' => (string) Str::uuid()], $meta),
        ]);
    }

    protected function errorResponse(string $message, string $code = 'error', ?string $field = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'data' => null,
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                    'field' => $field,
                ],
            ],
            'meta' => ['request_id' => (string) Str::uuid()],
        ], $status);
    }
}
