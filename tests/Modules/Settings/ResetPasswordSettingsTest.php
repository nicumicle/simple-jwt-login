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
        $resetPassSettings = (new ResetPasswordSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $resetPassSettings->initSettingsFromPost();
        $resetPassSettings->validateSettings();
        $this->assertSame(
            true,
            $resetPassSettings->isResetPasswordEnabled()
        );
        $this->assertSame(
            true,
            $resetPassSettings->isAuthKeyRequired()
        );
        $this->assertSame(
            ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL,
            $resetPassSettings->getFlowType()
        );
        $this->assertSame(
            'test subject',
            $resetPassSettings->getResetPasswordEmailSubject()
        );
        $this->assertSame(
            '{{CODE}} testbody',
            $resetPassSettings->getResetPasswordEmailBody()
        );
        $this->assertSame(
            ResetPasswordSettings::EMAIL_TYPE_HTML,
            $resetPassSettings->getResetPasswordEmailType()
        );

        $variables = array_keys($resetPassSettings->getEmailContentVariables());
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
        $resetPassSettings = (new ResetPasswordSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $resetPassSettings->initSettingsFromPost();
        $resetPassSettings->validateSettings();
    }
}
