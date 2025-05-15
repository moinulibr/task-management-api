<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // ModelNotFoundException handling
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Resource Not Found!', Response::HTTP_NOT_FOUND);
            }
        });

        // NotFoundHttpException handling (including underlying ModelNotFoundException)
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                // Check if previous exception is ModelNotFoundException
                if ($e->getPrevious() instanceof ModelNotFoundException) {
                    return $this->errorResponse('Resource Not Found!', Response::HTTP_NOT_FOUND);
                }
                // Otherwise normal 404 for invalid routes
                return $this->errorResponse('Endpoint not found!', Response::HTTP_NOT_FOUND);
            }
        });

        // Validation Exception handling
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed!',
                    'success' => false,
                    'error' => true,
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        // Authentication Exception handling
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Unauthenticated!', Response::HTTP_UNAUTHORIZED);
            }
        });

        // Fallback for any other exceptions
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return $this->errorResponse(
                    config('app.debug') ? $e->getMessage() : 'Server Error!',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        });
    }

    // Custom reusable error response format
    private function errorResponse(string $message, int $statusCode)
    {
        return response()->json([
            'message' => $message,
            'success' => false,
            'error' => true,
            'statusCode' => $statusCode,
            'data' => null,
        ], $statusCode);
    }
}
