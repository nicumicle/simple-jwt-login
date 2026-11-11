<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\ThirdParty;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AiAbilitiesSettingsTest extends TestCase
{
    /**
     * @return array<string, array{0: array, 1: string, 2: string, 3: bool, 4: bool}>
     */
    public static function accessProvider()
    {
        return [
            'nothing stored'            => [[], 'off', 'read', false, false],
            'disabled keeps selection'  => [['enabled' => false, 'access' => 'read_write'], 'off', 'read_write', false, false],
            'enabled without level'     => [['enabled' => true], 'read', 'read', true, false],
            'read only'                 => [['enabled' => true, 'access' => 'read'], 'read', 'read', true, false],
            'write only'                => [['enabled' => true, 'access' => 'write'], 'write', 'write', false, true],
            'read and write'            => [['enabled' => true, 'access' => 'read_write'], 'read_write', 'read_write', true, true],
            'unknown level is off'      => [['enabled' => true, 'access' => 'admin'], 'off', 'read', false, false],
        ];
    }

    #[DataProvider('accessProvider')]
    public function testAccess($stored, $access, $selected, $canRead, $canWrite)
    {
        $settings = (new AiAbilitiesSettings())->withSettings($stored);

        $this->assertSame($access, $settings->getAccess());
        $this->assertSame($selected, $settings->getSelectedAccess());
        $this->assertSame($canRead, $settings->isReadAllowed());
        $this->assertSame($canWrite, $settings->isWriteAllowed());
    }

    public function testAdminFormIsStoredUnderThirdParty()
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('sanitizeTextField')->willReturnArgument(0);

        $integrations = (new IntegrationsSettings())
            ->withSettings([])
            ->withWordPressData($wordPressData)
            ->withPost(['ai_abilities' => ['enabled' => '1', 'access' => 'write', 'allow_change_roles' => '1']]);
        $integrations->initSettingsFromPost();

        $this->assertSame([
            'enabled'               => true,
            'access'                => 'write',
            'allow_view_secrets'    => false,
            'allow_change_roles'    => true,
            'allow_change_auth_providers' => false,
            'allow_create_api_keys' => false,
            'allow_add_webhooks'    => false,
        ], $integrations->getSettings()['integrations']['3rdparty']['ai_abilities']);
        $this->assertTrue($integrations->aiAbilities()->isWriteAllowed());
        $this->assertFalse($integrations->aiAbilities()->isReadAllowed());
    }

    /**
     * @return array<string, array{0: array, 1: string, 2: bool}>
     */
    public static function permissionProvider()
    {
        return [
            'nothing stored is denied'      => [[], AiAbilitiesSettings::PERMISSION_VIEW_SECRETS, false],
            'stored false is denied'        => [['allow_change_roles' => false], AiAbilitiesSettings::PERMISSION_CHANGE_ROLES, false],
            'view secrets granted'          => [['allow_view_secrets' => true], AiAbilitiesSettings::PERMISSION_VIEW_SECRETS, true],
            'change roles granted'          => [['allow_change_roles' => true], AiAbilitiesSettings::PERMISSION_CHANGE_ROLES, true],
            'create api keys granted'       => [['allow_create_api_keys' => true], AiAbilitiesSettings::PERMISSION_CREATE_API_KEYS, true],
            'add webhooks granted'          => [['allow_add_webhooks' => true], AiAbilitiesSettings::PERMISSION_ADD_WEBHOOKS, true],
            'other permission not implied'  => [['allow_view_secrets' => true], AiAbilitiesSettings::PERMISSION_CHANGE_ROLES, false],
            'unknown permission is denied'  => [['enabled' => true], 'enabled', false],
        ];
    }

    #[DataProvider('permissionProvider')]
    public function testPermissionsDefaultToDenied($stored, $permission, $expected)
    {
        $settings = (new AiAbilitiesSettings())->withSettings($stored);

        $this->assertSame($expected, $settings->isPermissionGranted($permission));
    }

    public function testAdminFormParsesPermissions()
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('sanitizeTextField')->willReturnArgument(0);

        $integrations = (new IntegrationsSettings())
            ->withSettings([])
            ->withWordPressData($wordPressData)
            ->withPost(['ai_abilities' => [
                'enabled' => '1', 'access' => 'write',
                'allow_change_roles' => '1', 'allow_view_secrets' => '0',
            ]]);
        $integrations->initSettingsFromPost();

        $this->assertTrue($integrations->aiAbilities()->isPermissionGranted(AiAbilitiesSettings::PERMISSION_CHANGE_ROLES));
        $this->assertFalse($integrations->aiAbilities()->isPermissionGranted(AiAbilitiesSettings::PERMISSION_VIEW_SECRETS));
    }
}
