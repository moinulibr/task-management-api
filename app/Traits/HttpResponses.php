<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HttpResponses
{
    /**
     * Success response
     *
     * @param $data
     * @param $message
     * @param int $code
     * @return JsonResponse
     */
    protected function success($message = null, int $code = 200, $data, $isSuccess = true, $isError = false) : JsonResponse
    {
        return response()->json([
            'message' => $message,
            'success' => $isSuccess,
            'error' => $isError,
            'statusCode' => $code,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     *
     * @param $data
     * @param $message
     * @param $code
     * @return JsonResponse
     */
    protected function error($data = null, $message = '', $code = 500) : JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $code);
    }
}