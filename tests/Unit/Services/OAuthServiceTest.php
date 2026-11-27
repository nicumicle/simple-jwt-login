<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\OAuthService;

class OAuthServiceTest extends TestCase
{
    #[DataProvider('invalidProviderProvider')]
    /**
     * @param array<string,mixed> $request
     */
    public function testInvalidProviderIsRejected($request)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The Oauth provider is invalid.');
        $this->expectExceptionCode(ErrorCodes::ERR_OAUTH_INVALID_PROVIDER);

        (new OAuthService())
            ->withRequest($request)
            ->makeAction();
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function invalidProviderProvider()
    {
        return [
            'provider missing' => ['request' => []],
            'unknown provider' => ['request' => ['provider' => 'unknown']],
            'provider sent as an object' => ['request' => ['provider' => []]],
            'provider sent as a list' => ['request' => ['provider' => ['google']]],
        ];
    }
}
