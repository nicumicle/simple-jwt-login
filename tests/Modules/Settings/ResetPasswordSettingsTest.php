<?php

namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class ResetPasswordSettingsTest extends TestCase
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

    public function testAssignProperties()
    {
        $post                  = [
            'allow_reset_password'              => '1',
            'reset_password_requires_auth_code' => '1',
            'jwt_reset_password_flow'           => ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL,
            'jwt_email_subject'                 => 'test subject',
            'require_register_auth'             => '0',
            'jwt_reset_password_email_body'     => '{{CODE}} testbody',
            'jwt_email_type'                    => ResetPasswordSettings::EMAIL_TYPE_HTML,
        ];
        $resetPasswordSettings = (new ResetPasswordSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $resetPasswordSettings->initSettingsFromPost();
        $resetPasswordSettings->validateSettings();
        $this->assertSame(
            true,
            $resetPasswordSettings->isResetPasswordEnabled()
        );
        $this->assertSame(
            true,
            $resetPasswordSettings->isAuthKeyRequired()
        );
        $this->assertSame(
            ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL,
            $resetPasswordSettings->getFlowType()
        );
        $this->assertSame(
            'test subject',
            $resetPasswordSettings->getResetPasswordEmailSubject()
        );
        $this->assertSame(
            '{{CODE}} testbody',
            $resetPasswordSettings->getResetPasswordEmailBody()
        );
        $this->assertSame(
            ResetPasswordSettings::EMAIL_TYPE_HTML,
            $resetPasswordSettings->getResetPasswordEmailType()
        );

        $variables = array_keys($resetPasswordSettings->getEmailContentVariables());
        $this->assertTrue(! empty($variables));
        $this->assertTrue(in_array('{{CODE}}', $variables));
    }

    public function testValidation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You need to add the {{CODE}} variable in email body.');
        $post                  = [
            'allow_reset_password'              => '1',
            'reset_password_requires_auth_code' => '1',
            'jwt_reset_password_flow'           => ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL,
            'jwt_email_subject'                 => 'test subject',
            'require_register_auth'             => '0',
            'jwt_reset_password_email_body'     => 'test body',
            'jwt_email_type'                    => '1',
        ];
        $resetPasswordSettings = (new ResetPasswordSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $resetPasswordSettings->initSettingsFromPost();
        $resetPasswordSettings->validateSettings();
    }
}
