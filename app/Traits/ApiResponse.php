<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Envía una respuesta JSON exitosa
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = '', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message ?: 'Operación exitosa',
            'data' => $data
        ], $code);
    }

    /**
     * Envía una respuesta JSON de error
     *
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => null
        ], $code);
    }

    /**
     * Respuesta para recursos no encontrados (404)
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado')
    {
        return $this->errorResponse($message, 404);
    }
}