<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ValidatesRequestData
{
    /**
     * Validate request data with custom error handling
     * 
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @param array $messages
     * @return array|mixed Returns validated data or error response
     */
    protected function validateData($request, $rules, $messages = [])
    {
        try {
            return $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Handle any exception and return JSON error response
     * 
     * @param \Exception $e
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Exception $e, $message = 'Có lỗi xảy ra')
    {
        \Log::error($message . ': ' . $e->getMessage());

        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $message,
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return JSON error response
     * 
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $statusCode = Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'status_code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Return JSON success response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, $message = 'Thành công', $statusCode = Response::HTTP_OK)
    {
        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}
