<?php

namespace SimpleJWTLogin\Helpers;

use WP_REST_Response;

/**
 * Builds the plugin's `{"success":false,"data":{...}}` error envelope as a
 * WP_REST_Response, so errors go through the regular REST serving pipeline
 * (rest_post_dispatch, rest_pre_serve_request, CORS headers) instead of
 * terminating the request with wp_send_json_error().
 */
class RestErrorResponse
{
    /**
     * @param string $message
     * @param int $errorCode
     * @param int $statusCode
     *
     * @return WP_REST_Response
     */
    public static function create($message, $errorCode, $statusCode)
    {
        $response = new WP_REST_Response(
            [
                'success' => false,
                'data'    => [
                    'message'    => $message,
                    'error_code' => $errorCode,
                ],
            ]
        );
        $response->set_status($statusCode);

        return $response;
    }

    /**
     * @param \Throwable $exception
     *
     * @return WP_REST_Response
     */
    public static function fromException($exception)
    {
        return self::create(
            $exception->getMessage(),
            $exception->getCode(),
            StatusCodeHelper::getStatusCodeFromException($exception)
        );
    }
}
