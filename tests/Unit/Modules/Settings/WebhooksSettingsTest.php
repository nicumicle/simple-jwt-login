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
                'items' => [
                    ['url' => 'https://example.com', 'enabled' => true, 'events' => ['login']],
                ],
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
            'login: only enabled'    => [['items' => $webhooks], 'login',    1],
            'register: only enabled' => [['items' => $webhooks], 'register', 1],
            'auth: only enabled'     => [['items' => $webhooks], 'auth',     1],
            'no match for unknown'   => [['items' => $webhooks], 'unknown',  0],
        ];
    }

    public function testValidateThrowsOnEnabledWebhookWithInvalidUrl()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Webhook #1: invalid URL.');

        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['items' => [
                ['url' => 'not-a-url', 'enabled' => true, 'events' => ['login']],
            ]]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();
    }

    public function testValidateSkipsDisabledWebhooks()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['items' => [
                ['url' => 'not-a-url', 'enabled' => false, 'events' => ['login']],
            ]]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $this->expectNotToPerformAssertions();
        $settings->validateSettings();
    }

    public function testValidatePassesWithValidUrl()
    {
        $this->expectNotToPerformAssertions();
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['items' => [
                ['url' => 'https://example.com/hook', 'enabled' => true, 'events' => ['login']],
            ]]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();
    }

    #[DataProvider('insecureUrlProvider')]
    public function testValidateThrowsOnNonHttpsUrl(string $url)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Webhook #1: only HTTPS URLs are allowed.');

        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['items' => [
                ['url' => $url, 'enabled' => true, 'events' => ['login']],
            ]]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->validateSettings();
    }

    public static function insecureUrlProvider(): array
    {
        return [
            'plain http'         => ['http://example.com/hook'],
            'localhost http'     => ['http://localhost/admin'],
            'link-local'         => ['http://169.254.169.254/latest/meta-data/'],
            'private range'      => ['http://192.168.1.1/'],
            'ftp scheme'         => ['ftp://example.com/hook'],
        ];
    }

    public function testPayloadTemplateIsStoredWhenProvided()
    {
        // The browser base64-encodes payload_template before building webhooks_json
        // (see scripts.js), so the post data here already carries the encoded form.
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => base64_encode('id={{user_id}}')],
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

    public function testPayloadTemplateWithQuotesIsPreserved()
    {
        // A payload_template containing quotes/backslashes must never appear as raw
        // characters inside webhooks_json: WordPress's wp_magic_quotes() runs
        // stripslashes_deep() before addslashes_deep() on $_POST, which eats one layer
        // of any real backslash before our code ever sees it (e.g. a JSON.stringify()'d
        // embedded quote). Base64-encoding the field client-side avoids that entirely.
        $payloadTemplate = '{"user_id": "{{user_id}}"}';
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => base64_encode($payloadTemplate)],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame($payloadTemplate, $webhooks[0]['payload_template']);
    }

    public function testPayloadTemplateSurvivesWordPressMagicQuotesRoundTrip()
    {
        // Reproduces the full request pipeline: WordPress's wp_magic_quotes() runs
        // addslashes_deep(stripslashes_deep($_POST)) at boot, then
        // SimpleJWTLoginSettings::watchForUpdates() calls wp_unslash() (stripslashes_deep)
        // once more. For a raw payload_template containing a bare quote, that net
        // sequence is stripslashes(addslashes(stripslashes($raw))) == stripslashes($raw),
        // which eats the real backslash from a JSON.stringify()'d embedded quote and
        // breaks the JSON structure entirely - wiping out every webhook, not just this
        // field. Base64-encoding payload_template client-side means the wire value has
        // no quotes/backslashes for that pipeline to corrupt, so it must survive intact.
        $payloadTemplate = '"';
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => base64_encode($payloadTemplate)],
        ];
        $raw = json_encode($webhooksData);
        $afterMagicQuotes = addslashes(stripslashes($raw));
        $afterWpUnslash = stripslashes($afterMagicQuotes);

        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => $afterWpUnslash]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame($payloadTemplate, $webhooks[0]['payload_template']);
    }

    public function testPayloadTemplateWithTagsAndNewlinesIsPreserved()
    {
        // sanitize_text_field() would strip tags and collapse newlines/whitespace;
        // the payload template must survive untouched since it's an arbitrary HTTP
        // body (XML, form data, ...), not display text.
        $payloadTemplate = "<user>\n  <id>{{user_id}}</id>\n</user>";
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => base64_encode($payloadTemplate)],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame($payloadTemplate, $webhooks[0]['payload_template']);
    }

    public function testStoredPayloadTemplateIsBase64DecodedOnRead()
    {
        // base64-encoded, decoded unconditionally on read.
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['items' => [
                ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'payload_template' => base64_encode('id={{user_id}}')],
            ]]])
            ->withWordPressData($this->wordPressData)
            ->withPost(null);

        $webhooks = $settings->getWebhooks();
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

    public function testTimeoutDefaultsToZeroWhenNotProvided()
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
        $this->assertSame(WebhooksSettings::DEFAULT_TIMEOUT, $webhooks[0]['timeout']);
    }

    public function testTimeoutIsParsedFromPost()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'timeout' => '30'],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame(30, $webhooks[0]['timeout']);
    }

    public function testNegativeTimeoutClampsToZero()
    {
        $webhooksData = [
            ['url' => 'https://valid.com', 'enabled' => true, 'events' => ['login'], 'timeout' => '-10'],
        ];
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_json' => json_encode($webhooksData)]);
        $settings->initSettingsFromPost();

        $webhooks = $settings->getWebhooks();
        $this->assertCount(1, $webhooks);
        $this->assertSame(0, $webhooks[0]['timeout']);
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
            ->withSettings(['webhooks' => ['logs' => ['retention' => 14]]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);
        $settings->initSettingsFromPost();

        $this->assertSame(WebhooksSettings::DEFAULT_RETENTION_DAYS, $settings->getRetentionDays());
    }

    public function testGetRetentionDaysFromStoredSettings()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['logs' => ['retention' => 45]]])
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

    public function testIsEnabledDefaultsToFalseWhenNotSet()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(null);

        $this->assertFalse($settings->isEnabled());
    }

    public function testIsEnabledTrueFromPost()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_enabled' => '1']);
        $settings->initSettingsFromPost();

        $this->assertTrue($settings->isEnabled());
    }

    public function testIsEnabledFalseFromPost()
    {
        $settings = (new WebhooksSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost(['webhooks_enabled' => '0']);
        $settings->initSettingsFromPost();

        $this->assertFalse($settings->isEnabled());
    }

    public function testIsWebhookLogsEnabledFromStoredSettings()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['logs' => ['enabled' => true]]])
            ->withWordPressData($this->wordPressData)
            ->withPost(null);

        $this->assertTrue($settings->isWebhookLogsEnabled());
    }

    public function testIsWebhookLogsDisabledFromStoredSettings()
    {
        $settings = (new WebhooksSettings())
            ->withSettings(['webhooks' => ['logs' => ['enabled' => false]]])
            ->withWordPressData($this->wordPressData)
            ->withPost(null);

        $this->assertFalse($settings->isWebhookLogsEnabled());
    }
}
