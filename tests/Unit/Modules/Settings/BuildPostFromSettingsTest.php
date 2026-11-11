<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\Settings\SettingsFactory;
use SimpleJWTLogin\Modules\Settings\SettingsInterface;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class BuildPostFromSettingsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<SettingsInterface>, 1: array, 2: array}>
     */
    public static function postShapeProvider()
    {
        return [
            'general flat and grouped keys' => [
                GeneralSettings::class,
                ['general' => [
                    'route_namespace' => 'ns/v1/',
                    'request_keys'    => ['url' => 'JWT', 'header' => 'Authorization'],
                    'security'        => ['safe_redirect' => true],
                ]],
                [
                    'route_namespace' => 'ns/v1/',
                    'request_keys'    => ['url' => 'JWT', 'header' => 'Authorization'],
                    'security'        => ['safe_redirect' => true],
                ],
            ],
            'general base64 keys are decoded' => [
                GeneralSettings::class,
                ['general' => ['decryption_key_public' => base64_encode('PUBLIC')]],
                ['decryption_key_public' => 'PUBLIC'],
            ],
            'login uses renamed post keys' => [
                LoginSettings::class,
                ['login' => ['enabled' => true, 'login_by_parameter' => 'email']],
                ['allow_autologin' => true, 'jwt_login_by_parameter' => 'email'],
            ],
            'reset password email body decoded' => [
                ResetPasswordSettings::class,
                ['reset_password' => ['email_body' => base64_encode('Hello {{CODE}}')]],
                ['jwt_reset_password_email_body' => 'Hello {{CODE}}'],
            ],
            'oauth provider slice under its group' => [
                IntegrationsSettings::class,
                ['integrations' => ['oauth' => ['google' => ['enabled' => 1, 'client_id' => 'abc']]]],
                ['google' => ['enabled' => 1, 'client_id' => 'abc']],
            ],
            'webhook retention days' => [
                WebhooksSettings::class,
                ['webhooks' => ['enabled' => true, 'logs' => ['enabled' => true, 'retention' => 30]]],
                [WebhooksSettings::SETTING_RETENTION_DAYS => 30],
            ],
            'missing section yields empty post' => [
                LoginSettings::class,
                [],
                [],
            ],
        ];
    }

    /**
     * @param class-string<SettingsInterface> $className
     */
    #[DataProvider('postShapeProvider')]
    public function testBuildPostFromSettings($className, $settings, $expectedSubset)
    {
        $parser = (new $className())
            ->withSettings($settings)
            ->withWordPressData($this->createStub(WordPressDataInterface::class));

        $post = $parser->buildPostFromSettings();

        foreach ($expectedSubset as $key => $value) {
            $this->assertArrayHasKey($key, $post);
            $this->assertSame($value, $post[$key]);
        }
        if ($expectedSubset === []) {
            $this->assertSame([], $post);
        }
    }

    /**
     * Every settings class must survive a stored -> post -> stored round trip.
     *
     * @return array<string, array{0: int}>
     */
    public static function settingsTypeProvider()
    {
        $types = [];
        foreach ((new SettingsFactory())->getAll() as $type => $parser) {
            $types[get_class($parser)] = [$type];
        }

        return $types;
    }

    #[DataProvider('settingsTypeProvider')]
    public function testRoundTripIsStable($type)
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('sanitizeTextField')->willReturnArgument(0);

        $stored = json_decode((string) file_get_contents(__DIR__ . '/fixtures/stored-settings.json'), true);

        // The first pass may add defaults for keys missing from the stored array;
        // after that, stored -> post -> stored must be a fixpoint.
        $once  = $this->roundTrip($type, $wordPressData, $stored);
        $twice = $this->roundTrip($type, $wordPressData, $once);

        $this->assertEquals($once, $twice);
    }

    /**
     * @param int                    $type
     * @param WordPressDataInterface $wordPressData
     * @param array                  $settings
     * @return array
     */
    private function roundTrip($type, $wordPressData, array $settings)
    {
        $post = SettingsFactory::getFactory($type)
            ->withWordPressData($wordPressData)
            ->withSettings($settings)
            ->buildPostFromSettings();

        $parser = SettingsFactory::getFactory($type)->withWordPressData($wordPressData)->withSettings($settings);
        $parser->withPost($post)->initSettingsFromPost();

        return array_replace($settings, $parser->getSettings());
    }
}
