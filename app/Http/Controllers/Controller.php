<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Standardized success response without verbose data.
     */
    protected function successResponse($data = null, string $message = '', int $status = 200): JsonResponse
    {
        $response = [];
        if ($message) {
            $response['message'] = $message;
        }
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    /**
     * Minimal success response - no message, only data.
     */
    protected function minimalResponse($data = null, int $status = 200): JsonResponse
    {
        if ($data === null) {
            return response()->json([], $status);
        }
        return response()->json($data, $status);
    }
}
