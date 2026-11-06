<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\RestErrorResponse;
use TypeError;
use WP_REST_Response;

class RestErrorResponseTest extends TestCase
{
    public function testCreateBuildsErrorEnvelopeWithStatus()
    {
        $response = RestErrorResponse::create('Invalid refresh token.', 51, 401);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(401, $response->get_status());
        $this->assertSame(
            [
                'success' => false,
                'data'    => [
                    'message'    => 'Invalid refresh token.',
                    'error_code' => 51,
                ],
            ],
            $response->get_data()
        );
    }

    /**
     * @return array<string, array{0: \Throwable, 1: int}>
     */
    public static function exceptionProvider()
    {
        return [
            'mapped error code' => [
                new Exception('Invalid refresh token.', ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH),
                401,
            ],
            'php error is a server error' => [
                new TypeError('boom', 3),
                500,
            ],
        ];
    }

    #[DataProvider('exceptionProvider')]
    public function testFromExceptionUsesMessageCodeAndMappedStatus($exception, $expectedStatus)
    {
        $response = RestErrorResponse::fromException($exception);

        $this->assertSame($expectedStatus, $response->get_status());
        $this->assertSame(
            [
                'success' => false,
                'data'    => [
                    'message'    => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                ],
            ],
            $response->get_data()
        );
    }
}
