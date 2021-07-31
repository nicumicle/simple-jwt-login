<?php


namespace SimpleJWTLoginTests\Services;


use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\DeleteUserService;

class DeleteUserServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
    }

    /**
     * @dataProvider validationProvider
     * @param array $request
     * @param array $settings
     * @param string $exceptionMessage
     *
     * @throws Exception
     */
    public function testValidations($request, $settings, $exceptionMessage){
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock->method('getOptionFromDatabase')
                                ->willReturn(json_encode($settings));
        $deleteUserService = (new DeleteUserService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $deleteUserService->makeAction();
    }

    public function validationProvider()
    {
        return [
            'test_empty_settings_and_request' => [
                'request' => [],
                'settings' => [],
                'expectedException' => 'Delete is not enabled.',
            ],
            'test_missing_jwt_parameter' => [
                'request' => [],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'test_empty_jwt' => [
                'request' => [
                    'jwt' => ''
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'empty_upper_case_jwt' => [
                'request' => [
                    'JWT' => ''
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'test_missing_auth_code' => [
                'request' => [
                    'jwt' => '123.123.123',

                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'Missing AUTH KEY ( AUTH_KEY ).',
            ],
            'test_empty_auth_code' => [
                'request' => [
                    'jwt' => '123.123.123',
                    'AUTH_KEY' => '',
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'Missing AUTH KEY ( AUTH_KEY ).',
            ],

        ];
    }
}