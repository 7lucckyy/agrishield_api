<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiErrorResponse
{
    /**
     * @param  array<string, array<int, string>>  $errors
     * @param  array<string, string>  $headers
     */
    public static function make(
        Request $request,
        string $message,
        string $errorCode,
        int $status,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $requestId = $request->attributes->get('request_id');

        if (! is_string($requestId)) {
            $requestId = (string) Str::ulid();
            $request->attributes->set('request_id', $requestId);
        }

        $payload = [
            'message' => $message,
            'error_code' => $errorCode,
            'request_id' => $requestId,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }
}
