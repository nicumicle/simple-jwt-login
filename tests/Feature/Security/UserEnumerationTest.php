<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * The reset password request must not tell whether an email belongs to a user.
 */
class UserEnumerationTest extends FeatureTestCase
{
    /**
     * @return array<string, array{int}>
     */
    public static function flowProvider(): array
    {
        return [
            'save code in the database' => [ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB],
            'default WordPress email'   => [ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL],
            'custom email'              => [ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL],
        ];
    }

    #[DataProvider('flowProvider')]
    #[TestDox('Reset password answers the same for a known and an unknown email (flow $flow)')]
    public function testResetPasswordAnswersTheSameForUnknownEmails(int $flow): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'register'       => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'reset_password' => [
                'enabled'       => true,
                'flow'          => $flow,
                'email_subject' => 'Reset',
                'email_body'    => base64_encode('Your code: {{CODE}}'),
            ],
        ]));
        [$email, , $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $known   = $this->jsonRequest('POST', '/simple-jwt-login/v1/user/reset_password', ['email' => $email]);
        $unknown = $this->jsonRequest('POST', '/simple-jwt-login/v1/user/reset_password', [
            'email' => 'nobody-' . uniqid() . '@example.com',
        ]);

        $knownBody = (string) $known->getBody();
        $this->assertSame(200, $known->getStatusCode(), $knownBody);
        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode(), (string) $unknown->getBody());
        $this->assertSame($knownBody, (string) $unknown->getBody());
    }
}
