<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class WebhooksSettingsTest extends TestCase
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
            ->willReturnCallback(function ($value) {
                return $value;
            });
    }

    public function testEmptyPostPreservesExistingWebhooks()
    {
        $existing = [
            'webhooks' => [
                ['url' => 'https://example.com', 'enabled' => true, 'events' => ['login']],
            ],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings($existing)
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->initSettingsFromPost();

        $this->assertCount(1, $settings->getWebhooks());
    }

    public function testInitFromValidJsonPost()
    {
        $webhooksData = [
            ['url' => 'https://a.com/hook', 'enabled' => true,  'method' => 'PUT',  'events' => ['login', 'register'], 'headers' => [['key' => 'X-Foo', 'value' => 'bar']]],
            ['url' => 'https://b.com/hook', 'enabled' => false, 'method' => 'POST', 'events' => ['auth'],              'headers' => []],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(2, $webhooks);
        $this->assertSame('https://a.com/hook', $webhooks[0]['url']);
        $this->assertTrue($webhooks[0]['enabled']);
        $this->assertSame('PUT', $webhooks[0]['method']);
        $this->assertSame(['login', 'register'], $webhooks[0]['events']);
        $this->assertSame([['key' => 'X-Foo', 'value' => 'bar']], $webhooks[0]['headers']);
        $this->assertFalse($webhooks[1]['enabled']);
        $this->assertSame('POST', $webhooks[1]['method']);
    }

    public function testInvalidJsonResultsInEmptyWebhooks()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => 'not-valid-json']);
        $settings->initSettingsFromPost();

        $this->assertSame([], $settings->getWebhooks());
    }

    public function testInvalidMethodDefaultsToPost()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'method' => 'INVALID', 'events' => ['login'], 'headers' => []],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame(WebhooksSettings::DEFAULT_METHOD, $webhooks[0]['method']);
    }

    public function testMissingMethodDefaultsToPost()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login']],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame(WebhooksSettings::DEFAULT_METHOD, $webhooks[0]['method']);
    }

    public function testHeadersWithEmptyKeyAreSkipped()
    {
        $webhooksData = [
            [
                'url'     => 'https://valid.com',
                'enabled' => true,
                'events'  => ['login'],
                'headers' => [
                    ['key' => '',       'value' => 'ignored'],
                    ['key' => 'X-Keep', 'value' => 'yes'],
                ],
            ],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertCount(1, $webhooks[0]['headers']);
        $this->assertSame('X-Keep', $webhooks[0]['headers'][0]['key']);
    }

    public function testEntriesWithEmptyUrlAreSkipped()
    {
        $webhooksData = [
            ['url' => '',                    'enabled' => true, 'events' => ['login']],
            ['url' => 'https://valid.com',   'enabled' => true, 'events' => ['login']],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $this->assertCount(1, $settings->getWebhooks());
    }

    public function testUnknownEventsAreFiltered()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login', 'unknown_event']],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame(['login'], $webhooks[0]['events']);
    }

    #[DataProvider('eventFilterProvider')]
    public function testGetEnabledWebhooksForEvent(array $webhooks, string $event, int $expectedCount)
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => $webhooks])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);

        $this->assertCount($expectedCount, $settings->getEnabledWebhooksForEvent($event));
    }

    public static function eventFilterProvider(): array
    {
        $webhooks = [
            ['url' => 'https://a.com', 'enabled' => true,  'events' => ['login', 'register']],
            ['url' => 'https://b.com', 'enabled' => true,  'events' => ['auth']],
            ['url' => 'https://c.com', 'enabled' => false, 'events' => ['login']],
        ];
        return [
            'login: only enabled'    => [$webhooks, 'login',    1],
            'register: only enabled' => [$webhooks, 'register', 1],
            'auth: only enabled'     => [$webhooks, 'auth',     1],
            'no match for unknown'   => [$webhooks, 'unknown',  0],
        ];
    }

    public function testValidateThrowsOnEnabledWebhookWithInvalidUrl()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Webhook #1: invalid URL.');

        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => [
                ['url' => 'not-a-url', 'enabled' => true, 'events' => ['login']],
            ]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();
    }

    public function testValidateSkipsDisabledWebhooks()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => [
                ['url' => 'not-a-url', 'enabled' => false, 'events' => ['login']],
            ]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();

        $this->assertTrue(true);
    }

    public function testValidatePassesWithValidUrl()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => [
                ['url' => 'https://example.com/hook', 'enabled' => true, 'events' => ['login']],
            ]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();

        $this->assertTrue(true);
    }

    public function testPayloadTemplateIsStoredWhenProvided()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => 'id={{user_id}}'],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame('id={{user_id}}', $webhooks[0]['payload_template']);
    }

    public function testPayloadTemplateDefaultsToEmptyString()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login']],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame('', $webhooks[0]['payload_template']);
    }

    public function testRetentionDaysDefaultValue()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->initSettingsFromPost();

        $this->assertSame(WebhooksSettings::DEFAULT_RETENTION_DAYS, $settings->getRetentionDays());
    }

    public function testRetentionDaysFromPost()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([WebhooksSettings::SETTING_RETENTION_DAYS => '30']);
        $settings->initSettingsFromPost();

        $this->assertSame(30, $settings->getRetentionDays());
    }

    public function testRetentionDaysFromSettings()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([WebhooksSettings::SETTING_RETENTION_DAYS => 14])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->initSettingsFromPost();

        $this->assertSame(WebhooksSettings::DEFAULT_RETENTION_DAYS, $settings->getRetentionDays());
    }

    public function testGetRetentionDaysFromStoredSettings()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([WebhooksSettings::SETTING_RETENTION_DAYS => 45])
            ->withWordPressData($this->wordPressData)
            ->withPost(null);

        $this->assertSame(45, $settings->getRetentionDays());
    }

    public function testRetentionDaysMinimumEnforced()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([WebhooksSettings::SETTING_RETENTION_DAYS => '-5']);
        $settings->initSettingsFromPost();

        $this->assertSame(1, $settings->getRetentionDays());
    }

    public function testValidateRetentionDaysThrowsOnZero()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([WebhooksSettings::SETTING_RETENTION_DAYS => '0']);

        $this->expectException(\Exception::class);
        $settings->validateSettings();
    }
}
