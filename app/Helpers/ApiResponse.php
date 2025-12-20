<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success(
        $data = null,
        string $message = null,
        int $code = 200,
        string $title = 'Success'
    )
    {
        $response = [
            'code'    => $code,
            'title'   => $title,
            'message' => $message ?? __('Success'),
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    public static function error(
        string $message = null,
        $errors = null,
        int $code = 400,
        string $title = 'Error'
    )
    {
        $response = [
            'code'    => $code,
            'title'   => $title,
            'message' => $message ?? __('Something went wrong'),
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
