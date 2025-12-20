<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionHandler extends Exception
{
    public static function handle($exceptions)
    {
        // Tangani NotFoundHttpException
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => __('Data not found'),
                        'code' => 1,
                    ], 404);
                } else {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $e->getMessage() . " " . $e->getPrevious()->getMessage(),
                        'code' => 2,
                    ], 404);
                }
            }
        });


        // Tambah handler lain di sini jika perlu...
    }
}
