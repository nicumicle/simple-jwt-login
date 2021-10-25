<?php
namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class GeneralSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    public function testProperties()
    {
        $post = [
            'route_namespace' => 'jwt',
            'jwt_algorithm' => 'HS256',
            'decryption_source' => 0,
            'decryption_key' => '123',
            'decryption_key_base64' => false,
            'decryption_key_public' => null,
            'decryption_key_private' => null,
            'request_jwt_url' => '1',
            'request_jwt_cookie' => '1',
            'request_jwt_header' => '1',
            'request_jwt_session' => '1',
            'api_middleware' => [
                'enabled' => 1
            ],
            'request_keys' => [
                'url' => 'jwt1',
                'session' => 'jwt2',
                'cookie' => 'jwt3',
                'header' => 'jwt4'
            ]

        ];
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);

        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();

        $this->assertSame('0', $generalSettings->getDecryptionSource());
        $this->assertSame(false, $generalSettings->isDecryptionKeyBase64Encoded());
        $this->assertSame('123', $generalSettings->getDecryptionKey());
        $this->assertSame('', $generalSettings->getDecryptionKeyPublic());
        $this->assertSame('', $generalSettings->getDecryptionKeyPrivate());
        $this->assertSame('HS256', $generalSettings->getJWTDecryptAlgorithm());
        $this->assertSame('jwt/', $generalSettings->getRouteNamespace());
        $this->assertSame(true, $generalSettings->isJwtFromURLEnabled());
        $this->assertSame(true, $generalSettings->isJwtFromCookieEnabled());
        $this->assertSame(true, $generalSettings->isJwtFromHeaderEnabled());
        $this->assertSame(true, $generalSettings->isJwtFromSessionEnabled());
        $this->assertSame('jwt1', $generalSettings->getRequestKeyUrl());
        $this->assertSame('jwt2', $generalSettings->getRequestKeySession());
        $this->assertSame('jwt3', $generalSettings->getRequestKeyCookie());
        $this->assertSame('jwt4', $generalSettings->getRequestKeyHeader());
        $this->assertSame(true, $generalSettings->isMiddlewareEnabled());
    }

    public function testValidationFailsEmptyNamespace()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Route namespace could not be empty.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationEmptyRequestKeys()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Request Keys are required.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => '',
                        'session' => '',
                        'cookie' => '',
                        'header' => '',
                    ]
                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationDecryptionKeysFromCode()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Public or private key is not defined in code.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE,
                    'jwt_algorithm' => 'RS256',

                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationDecryptionKeysPrivateFromCode()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Private key is not defined in code.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE,
                    'jwt_algorithm' => 'HS256',
                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationDecryptionKeysPrivateFromCodeEmpty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Private key is not defined in code.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE,
                    'jwt_algorithm' => 'HS256',
                    'decryption_key_private' => ' ',
                    'decryption_key_public' => ' ',
                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationDecryptionKeysRSFromSettings()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('JWT Decryption public and private key are required.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_SETTINGS,
                    'jwt_algorithm' => 'RS256',
                    'decryption_key_public' => '',
                    'decryption_key_private' => '',

                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationDecryptionKeysHSFromSettings()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('JWT Decryption key is required.');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_SETTINGS,
                    'jwt_algorithm' => 'HS256',
                    'decryption_key' => ''

                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }

    public function testValidationGetJWTTokenFrom()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You have to have at least on option enabled in \'Get JWT token From\'');
        $generalSettings = (new GeneralSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(
                [
                    'route_namespace' => 'v1/',
                    'request_keys' => [
                        'url' => 'test',
                        'session' => 'test',
                        'cookie' => 'test',
                        'header' => 'test',
                    ],
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_SETTINGS,
                    'jwt_algorithm' => 'HS256',
                    'decryption_key' => '123',
                    'request_jwt_url' => 0,
                    'request_jwt_cookie' => 0,
                    'request_jwt_header' => 0,
                    'request_jwt_session' => 0,
                ]
            );
        $generalSettings->initSettingsFromPost();
        $generalSettings->validateSettings();
    }
}
