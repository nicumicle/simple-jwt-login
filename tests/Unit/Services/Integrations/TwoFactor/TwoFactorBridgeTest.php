<?php

namespace SimpleJwtLoginTests\Unit\Services\Integrations\TwoFactor {

    require_once __DIR__ . '/../../../Stubs/TwoFactorStubs.php';

    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
    use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
    use Two_Factor_Backup_Codes;
    use Two_Factor_Core;
    use Two_Factor_Email;
    use Two_Factor_Totp;

    class TwoFactorBridgeTest extends TestCase
    {
        public function testIsAvailableReturnsTrueWhenCoreClassExists()
        {
            $bridge = new TwoFactorBridge();
            $this->assertTrue($bridge->isAvailable());
        }

        public function testIsUserUsing2FADelegatesToCore()
        {
            Two_Factor_Core::$usingTwoFactor = true;
            $bridge = new TwoFactorBridge();
            $this->assertTrue($bridge->isUserUsing2FA('user'));

            Two_Factor_Core::$usingTwoFactor = false;
            $this->assertFalse($bridge->isUserUsing2FA('user'));
        }

        public function testGetPrimaryProviderDelegatesToCore()
        {
            Two_Factor_Core::$primaryProvider = 'Two_Factor_Email';
            $bridge = new TwoFactorBridge();
            $this->assertSame('Two_Factor_Email', $bridge->getPrimaryProvider('user'));
        }

        public function testCreateNonceDelegatesToCore()
        {
            Two_Factor_Core::$nonce = array('key' => 'value');
            $bridge = new TwoFactorBridge();
            $this->assertSame(array('key' => 'value'), $bridge->createNonce(7));
        }

        public function testVerifyNonceDelegatesToCore()
        {
            Two_Factor_Core::$verifyNonceResult = true;
            $bridge = new TwoFactorBridge();
            $this->assertTrue($bridge->verifyNonce(7, 'nonce'));

            Two_Factor_Core::$verifyNonceResult = false;
            $this->assertFalse($bridge->verifyNonce(7, 'nonce'));
        }

        public function testIsRateLimitedDelegatesToCore()
        {
            Two_Factor_Core::$rateLimited = true;
            $bridge = new TwoFactorBridge();
            $this->assertTrue($bridge->isRateLimited('user'));
        }

        public function testGetTimeDelayDelegatesToCore()
        {
            Two_Factor_Core::$timeDelay = 30;
            $bridge = new TwoFactorBridge();
            $this->assertSame(30, $bridge->getTimeDelay('user'));
        }

        public function testSetInterimCookieDoesNotThrow()
        {
            $this->expectNotToPerformAssertions();
            $bridge = new TwoFactorBridge();
            $bridge->setInterimCookie(7);
        }

        public function testDeleteNonceDelegatesToCore()
        {
            Two_Factor_Core::$deletedNonceFor = null;
            $bridge = new TwoFactorBridge();
            $bridge->deleteNonce(42);
            $this->assertSame(42, Two_Factor_Core::$deletedNonceFor);
        }

        #[DataProvider('failedAttemptsProvider')]
        /**
         * @param mixed $storedValue
         * @param int $expectedCount
         */
        public function testRecordFailedAttemptStoresTimestampAndIncrementsCounter($storedValue, $expectedCount)
        {
            $wordPressData = $this->createMock(WordPressDataInterface::class);
            $wordPressData->method('getUserMeta')
                ->with(7, '_two_factor_failed_login_attempts')
                ->willReturn($storedValue);

            $written = [];
            $wordPressData->expects($this->exactly(2))
                ->method('updateUserMeta')
                ->willReturnCallback(function ($userId, $metaKey, $value) use (&$written) {
                    $written[$metaKey] = [$userId, $value];
                    return true;
                });
            $wordPressData->method('getUserProperty')->willReturnMap([['user', 'user_login', 'jdoe']]);
            $wordPressData->expects($this->once())->method('loginFailed')->with('jdoe');

            $before = time();
            (new TwoFactorBridge($wordPressData))->recordFailedAttempt('user', 7);

            $this->assertSame([7, (string) $expectedCount], $written['_two_factor_failed_login_attempts']);
            $this->assertSame(7, $written['_two_factor_last_login_failure'][0]);
            $this->assertGreaterThanOrEqual($before, (int) $written['_two_factor_last_login_failure'][1]);
            $this->assertLessThanOrEqual(time(), (int) $written['_two_factor_last_login_failure'][1]);
        }

        /**
         * @return array[]
         */
        public static function failedAttemptsProvider()
        {
            return [
                'no previous failures (empty meta list)' => [[], 1],
                'no previous failures (empty string)' => [[''], 1],
                'one previous failure' => [['1'], 2],
                'three previous failures' => [['3'], 4],
                'scalar meta value' => ['5', 6],
            ];
        }

        #[DataProvider('passwordResetProvider')]
        public function testRecordFailedAttemptResetsThePasswordAfterTooManyFailures(bool $limitReached)
        {
            Two_Factor_Core::$shouldResetPassword = $limitReached;
            Two_Factor_Core::$passwordResetFor    = null;
            Two_Factor_Core::$resetEmailsSentFor  = null;

            $wordPressData = $this->createStub(WordPressDataInterface::class);
            $wordPressData->method('getUserMeta')->willReturn(['29']);

            $passwordWasReset = (new TwoFactorBridge($wordPressData))->recordFailedAttempt('user', 7);
            $expectedUser     = $limitReached ? 'user' : null;

            Two_Factor_Core::$shouldResetPassword = false;

            $this->assertSame($limitReached, $passwordWasReset);
            $this->assertSame($expectedUser, Two_Factor_Core::$passwordResetFor);
            $this->assertSame($expectedUser, Two_Factor_Core::$resetEmailsSentFor);
        }

        /**
         * @return array[]
         */
        public static function passwordResetProvider()
        {
            return [
                'limit reached' => [true],
                'limit not reached' => [false],
            ];
        }

        public function testResetFailedAttemptsDeletesBothCounters()
        {
            $wordPressData = $this->createMock(WordPressDataInterface::class);

            $deleted = [];
            $wordPressData->expects($this->exactly(2))
                ->method('deleteUserMeta')
                ->willReturnCallback(function ($userId, $metaKey) use (&$deleted) {
                    $deleted[] = [$userId, $metaKey];
                    return true;
                });

            (new TwoFactorBridge($wordPressData))->resetFailedAttempts(7);

            $this->assertSame(
                [
                    [7, '_two_factor_last_login_failure'],
                    [7, '_two_factor_failed_login_attempts'],
                ],
                $deleted
            );
        }

        #[DataProvider('verifyCodeProvider')]
        /**
         * @param string $providerClass
         * @param bool $providerResult
         * @param bool $expectedResult
         */
        public function testVerifyCode($providerClass, $providerResult, $expectedResult)
        {
            Two_Factor_Totp::$result = $providerResult;
            Two_Factor_Email::$result = $providerResult;
            Two_Factor_Backup_Codes::$result = $providerResult;

            $bridge = new TwoFactorBridge();
            $this->assertSame(
                $expectedResult,
                $bridge->verifyCode($providerClass, 'user', '123456', 7)
            );
        }

        /**
         * @return array[]
         */
        public static function verifyCodeProvider()
        {
            return [
                'totp valid' => ['Two_Factor_Totp', true, true],
                'totp invalid' => ['Two_Factor_Totp', false, false],
                'email valid' => ['Two_Factor_Email', true, true],
                'email invalid' => ['Two_Factor_Email', false, false],
                'backup codes valid' => ['Two_Factor_Backup_Codes', true, true],
                'backup codes invalid' => ['Two_Factor_Backup_Codes', false, false],
                'unknown provider returns false' => ['Two_Factor_Unknown', true, false],
            ];
        }
    }
}
