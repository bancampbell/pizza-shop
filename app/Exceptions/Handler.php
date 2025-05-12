<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends Exception
{
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            $statusCode = match (true) {
                $e instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $e instanceof LogicException => Response::HTTP_BAD_REQUEST, // Теперь LogicException → 400
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return response()->json([
                'error' => $e->getMessage(),
            ], $statusCode);
        }

        return parent::render($request, $e);
    }
}
