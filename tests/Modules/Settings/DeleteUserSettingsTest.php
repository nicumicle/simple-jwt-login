<?php

namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class DeleteUserSettingsTest extends TestCase
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
            'allow_delete' => '1',
            'require_delete_auth' => '0',
            'delete_ip' => '127.0.0.1',
            'allowed_user_meta' => 'meta1',
            'delete_user_by' => '1',
            'jwt_delete_by_parameter' => 'id',
        ];
        $deleteUserSetings = (new DeleteUserSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);

        $deleteUserSetings->initSettingsFromPost();
        $deleteUserSetings->validateSettings();

        $this->assertSame(
            true,
            $deleteUserSetings->isDeleteAllowed()
        );
        $this->assertSame(
            false,
            $deleteUserSetings->isAuthKeyRequiredOnDelete()
        );
        $this->assertSame(
            '127.0.0.1',
            $deleteUserSetings->getAllowedDeleteIps()
        );
        $this->assertSame(
            1,
            $deleteUserSetings->getDeleteUserBy()
        );
        $this->assertSame(
            'id',
            $deleteUserSetings->getJwtDeleteByParameter()
        );
    }

    public function testValidate()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing JWT parameter for Delete User.');
        $deleteUserSettings = (new DeleteUserSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([
                'allow_delete' => 1,
                'jwt_delete_by_parameter' => ''
            ]);
        $deleteUserSettings->initSettingsFromPost();
        $deleteUserSettings->validateSettings();
    }
}
