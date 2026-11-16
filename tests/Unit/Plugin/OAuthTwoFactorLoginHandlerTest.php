<?php

namespace SimpleJwtLoginTests\Unit\Plugin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Plugin\OAuthTwoFactorLoginHandler;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

class OAuthTwoFactorLoginHandlerTest extends TestCase
{
    /** @var MockObject|WordPressDataInterface */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->wordPressData->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $this->wordPressData->method('getUserDetailsById')->willReturn('user');
        $this->wordPressData->method('isInstanceOfuser')->willReturn(true);
    }

    /**
     * @param TwoFactorBridge $bridge
     * @param array $postVars
     * @return OAuthTwoFactorLoginHandler
     */
    private function makeHandler($bridge, array $postVars)
    {
        $settings = new SimpleJWTLoginSettings($this->wordPressData);

        return new class (
            ['REQUEST_METHOD' => 'POST'],
            [],
            array_merge(['sjl_jwt' => 'interim.jwt', 'redirect_to' => '/dashboard'], $postVars),
            $settings,
            $bridge
        ) extends OAuthTwoFactorLoginHandler {
            /** @var TwoFactorBridge */
            private $testBridge;

            /** @var string|null */
            public $renderedError = null;

            /** @var string|null */
            public $abortMessage = null;

            public function __construct($serverVars, $getVars, $postVars, $settings, $bridge)
            {
                parent::__construct($serverVars, $getVars, $postVars, $settings);
                $this->testBridge = $bridge;
            }

            protected function getBridge()
            {
                return $this->testBridge;
            }

            protected function decodeAndValidate($jwt)
            {
                unset($jwt);
                return [
                    'tfa_user_id'  => 42,
                    'tfa_nonce'    => 'nonce',
                    'tfa_provider' => 'Two_Factor_Totp',
                ];
            }

            protected function renderForm($jwt, $redirectTo, $error)
            {
                unset($jwt, $redirectTo);
                $this->renderedError = $error;
            }

            protected function abortToLoginPage($message)
            {
                $this->abortMessage = $message;
            }
        };
    }

    /**
     * @param bool $codeIsValid
     * @return MockObject|TwoFactorBridge
     */
    private function makeBridge($codeIsValid)
    {
        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->method('isRateLimited')->willReturn(false);
        $bridge->method('verifyNonce')->willReturn(true);
        $bridge->method('verifyCode')->willReturn($codeIsValid);
        return $bridge;
    }

    public function testInvalidCodeRecordsFailedAttempt(): void
    {
        $bridge = $this->makeBridge(false);
        $bridge->expects($this->once())->method('recordFailedAttempt')->with('user', 42)->willReturn(false);
        $bridge->expects($this->never())->method('resetFailedAttempts');
        $bridge->expects($this->never())->method('deleteNonce');

        $handler = $this->makeHandler($bridge, ['sjl_code' => '000000']);
        $handler->handleAction();

        $this->assertSame('Invalid two-factor code. Please try again.', $handler->renderedError);
    }

    public function testPasswordResetAfterTooManyFailuresEndsTheLogin(): void
    {
        $bridge = $this->makeBridge(false);
        $bridge->expects($this->once())->method('recordFailedAttempt')->with('user', 42)->willReturn(true);

        $handler = $this->makeHandler($bridge, ['sjl_code' => '000000']);
        $handler->handleAction();

        $this->assertNull($handler->renderedError);
        $this->assertSame(
            'Too many invalid two-factor codes. Your password was reset, check your email to set a new one.',
            $handler->abortMessage
        );
    }

    public function testValidCodeResetsFailedAttempts(): void
    {
        $bridge = $this->makeBridge(true);
        $bridge->expects($this->never())->method('recordFailedAttempt');
        $bridge->expects($this->once())->method('resetFailedAttempts')->with(42);
        $bridge->expects($this->once())->method('deleteNonce')->with(42);

        $handler = $this->makeHandler($bridge, ['sjl_code' => '123456']);
        $handler->handleAction();

        $this->assertNull($handler->renderedError);
        $this->assertNull($handler->abortMessage);
    }

    #[DataProvider('rejectedBeforeCodeCheckProvider')]
    public function testRequestsRejectedBeforeTheCodeCheckDoNotTouchTheCounter(
        bool $rateLimited,
        bool $nonceIsValid,
        array $postVars
    ): void {
        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->method('isRateLimited')->willReturn($rateLimited);
        $bridge->method('verifyNonce')->willReturn($nonceIsValid);
        $bridge->expects($this->never())->method('verifyCode');
        $bridge->expects($this->never())->method('recordFailedAttempt');
        $bridge->expects($this->never())->method('resetFailedAttempts');

        $handler = $this->makeHandler($bridge, $postVars);
        $handler->handleAction();

        $this->assertTrue($handler->renderedError !== null || $handler->abortMessage !== null);
    }

    /**
     * @return array[]
     */
    public static function rejectedBeforeCodeCheckProvider(): array
    {
        return [
            'rate limited' => [true, true, ['sjl_code' => '000000']],
            'invalid nonce' => [false, false, ['sjl_code' => '000000']],
            'missing code' => [false, true, []],
        ];
    }

    public function testUnknownUserAbortsWithoutTouchingTheCounter(): void
    {
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->wordPressData->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $this->wordPressData->method('getUserDetailsById')->willReturn(false);
        $this->wordPressData->method('isInstanceOfuser')->willReturn(false);

        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->expects($this->never())->method('recordFailedAttempt');

        $handler = $this->makeHandler($bridge, ['sjl_code' => '000000']);
        $handler->handleAction();

        $this->assertSame('User not found.', $handler->abortMessage);
    }
}
