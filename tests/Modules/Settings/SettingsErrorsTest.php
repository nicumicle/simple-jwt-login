<?php
namespace SimpleJwtLoginTests\Modules\Settings;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

class SettingsErrorsTest extends TestCase
{
    public function testEmptyGetSectionFromErrorCode()
    {
        $settingsErrors = new SettingsErrors();
        $this->assertSame(0, $settingsErrors->getSectionFromErrorCode(0));
    }

    public function testGetSectionFromErrorCode()
    {
        $settingsErrors = new SettingsErrors();
        $errorCode = ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED;
        $section = SettingsErrors::PREFIX_AUTHENTICATION;
        $generatedCode = $settingsErrors->generateCode($section, $errorCode);
        $this->assertSame(
            SettingsErrors::PREFIX_LEEWAY * $section + $errorCode,
            $generatedCode
        );
        $this->assertSame(
            $section,
            $settingsErrors->getSectionFromErrorCode($generatedCode)
        );
    }
}
