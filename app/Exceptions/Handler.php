<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function invalidJson($request, ValidationException $exception): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'errors' => collect($exception->errors())->map(function ($message, $field) use
            ($exception) {
                return [
                    'status' => '422',
                    'title'  => 'Validation Error',
                    'details' => $message[0],
                    'source' => [
                        'pointer' => '/data/attributes/' . $field
                    ]
                ];
            })->values()
        ], $exception->status);
    }
public function render($request, Throwable $exception)
{
    // Manejo personalizado de ValidationException
    if ($exception instanceof ValidationException) {
        return $this->invalidJson($request, $exception);
    }
    // Manejo personalizado de QueryException (como ejemplo para errores de base de datos)
    if ($exception instanceof QueryException) {
        return response()->json([
            'errors' => [
                [
                    'status' => '500',
                    'title' => 'Database Error',
                    'detail' => 'Error procesando la respuesta. Inténtelo más tarde.'
                ]
            ]
        ], 500);
    }
    // Delegar a la implementación predeterminada para otras excepciones no manejadas
    return parent::render($request, $exception);
}
}
