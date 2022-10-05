<?php

namespace SimpleJwtLoginTests\Libraries;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\JWT\JWT;

class JWTTest extends TestCase
{
    public function testSuccessJWT()
    {
        $payload = [
            'data' => 123
        ];
        $key = '123';
        $alg = 'HS256';
        $jwt = JWT::encode($payload, $key, $alg);
        $decoded = JWT::decode($jwt, $key, [$alg]);
        $this->assertSame($payload, (array) $decoded);
    }

    /**
     * @dataProvider invalidJwtProvider
     * @throws Exception
     */
    public function testWrongNumberOfSegments($jwtString)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong number of segments');
        $result = JWT::decode($jwtString, '123');
        $this->assertTrue($result);
    }

    /**
     * @return string[][]
     */
    public function invalidJwtProvider()
    {
        return [
            [ '' ],
            [ '123' ],
            [ '123.33' ],
        ];
    }

    public function testInvalidAlgForEncode()
    {
        $this->expectExceptionMessage('Algorithm not supported');
        $result = JWT::encode([], '123', 'ABC123');
        $this->assertTrue($result);
    }
    public function testInvalidAlgForDecode()
    {
        $this->expectExceptionMessage('Algorithm not allowed');
        $jwt = JWT::encode([], '123', 'HS256');
        $result = JWT::decode($jwt, '123', ['ABC123']);
        $this->assertTrue($result);
    }

    public function testDecodeWithEmptyKey()
    {
        $this->expectExceptionMessage('Key may not be empty');
        JWT::decode('', '', ['HS256']);
    }


    public function testJwtWithInvalidHeader()
    {
        $this->expectExceptionMessage('Syntax error, malformed JSON');
        $jwt = JWT::encode([], '123', 'HS256');
        $jwtArray = explode('.', $jwt);
        $jwtArray[0] = '2';
        JWT::decode(implode('.', $jwtArray), '123', ['HS256']);
    }

    public function testJwtWithInvalidSignature()
    {
        $this->expectExceptionMessage('Signature verification failed');
        $jwt = JWT::encode([], '123', 'HS256');
        $jwtArray = explode('.', $jwt);
        $jwtArray[2] = '2';
        JWT::decode(implode('.', $jwtArray), '123', ['HS256']);
    }

    public function testJwtWithInvalidPayload()
    {
        $this->expectExceptionMessage('Syntax error, malformed JSON');
        $jwt = JWT::encode([], '123', 'HS256');
        $jwtArray = explode('.', $jwt);
        $jwtArray[1] = '2';
        JWT::decode(implode('.', $jwtArray), '123', ['HS256']);
    }

    public function testJWtWithModifiedPayload()
    {
        $this->expectExceptionMessage('Malformed UTF-8 characters');
        $jwt = JWT::encode([], '123', 'HS256');
        $jwtArray = explode('.', $jwt);
        $jwtArray[1] = json_encode(['123' => '123']);
        JWT::decode(implode('.', $jwtArray), '123', ['HS256']);
    }
}
