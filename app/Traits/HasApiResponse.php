<?php

namespace App\Traits;

trait HasApiResponse
{
    function successResponse($data, $message = 'Success', $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $code);
    }

    function errorResponse($message = 'Error', $code = 500): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message
        ], $code);
    }
}
