<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response helper
 */
class ApiResponse
{
    /**
     * Return a success response
     */
    public static function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response = array_merge($response, is_array($data) ? $data : ['data' => $data]);
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response
     */
    public static function error(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an accepted response (for async operations)
     */
    public static function accepted(?string $message = 'Request accepted for processing'): JsonResponse
    {
        return response()->json([
            'status' => 'accepted',
            'message' => $message,
        ], 202);
    }
}
