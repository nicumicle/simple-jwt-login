<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class DeleteUserSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
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
            'allow_delete' => '1',
            'require_delete_auth' => '0',
            'delete_ip' => '127.0.0.1',
        ];
        $deleteUserSettings = (new DeleteUserSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);

        $deleteUserSettings->initSettingsFromPost();
        $deleteUserSettings->validateSettings();

        $this->assertTrue($deleteUserSettings->isDeleteAllowed());
        $this->assertFalse($deleteUserSettings->isAuthKeyRequiredOnDelete());
        $this->assertSame('127.0.0.1', $deleteUserSettings->getAllowedDeleteIps());
    }
}
