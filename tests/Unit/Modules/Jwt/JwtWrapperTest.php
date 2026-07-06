<?php

namespace SimpleJwtLoginTests\Unit\Modules\Jwt;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\JWTException;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;

class JwtWrapperTest extends TestCase
{
    /** @var JwtWrapper */
    private $wrapper;

    protected function setUp(): void
    {
        $this->wrapper = new JwtWrapper();
    }

    public function testDecodeThrowsJWTExceptionOnEmptyKey(): void
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionCode(ErrorCodes::ERR_EMPTY_KEY);

        $this->wrapper->decode('a.b.c', '', ['HS256']);
    }

    public function testDecodeThrowsJWTExceptionOnMalformedJwt(): void
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionCode(ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS);

        $this->wrapper->decode('not-a-jwt', 'secret', ['HS256']);
    }

    public function testEncodeThrowsJWTExceptionOnUnsupportedAlgorithm(): void
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionCode(ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_IN_SIGNATURE);

        $this->wrapper->encode(['sub' => 1], 'secret', 'INVALID_ALG');
    }

    public function testExtractDataFromJwtThrowsJWTExceptionOnMalformedJwt(): void
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionCode(ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS);

        $this->wrapper->extractDataFromJwt('not-a-jwt');
    }

    public function testJWTExceptionPreservesMessage(): void
    {
        try {
            $this->wrapper->decode('bad', 'secret', ['HS256']);
            $this->fail('Expected JWTException was not thrown');
        } catch (JWTException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }

    public function testEncodeDecodeRoundtripSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = ['sub' => 42, 'iat' => time()];
        $key = 'test-secret-key';
        $alg = 'HS256';

        $token = $this->wrapper->encode($payload, $key, $alg);
        $this->wrapper->decode($token, $key, [$alg]);
    }

    public function testExtractDataFromJwtSucceeds(): void
    {
        $payload = ['sub' => 42, 'iat' => time()];
        $key = 'test-secret-key';
        $alg = 'HS256';

        $token = $this->wrapper->encode($payload, $key, $alg);
        $data = $this->wrapper->extractDataFromJwt($token);

        $this->assertArrayHasKey('header', $data);
        $this->assertArrayHasKey('payload', $data);
        $this->assertSame(42, $data['payload']['sub']);
    }
}
