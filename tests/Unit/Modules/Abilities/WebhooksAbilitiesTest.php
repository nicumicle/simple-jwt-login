<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\Abilities\Webhooks\AddWebhookAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\ListWebhooksAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\RemoveWebhookAbility;

#[RunTestsInSeparateProcesses]
class WebhooksAbilitiesTest extends AbilityTestCase
{
    /**
     * @return array
     */
    private function settingsWithWebhook()
    {
        $settings = $this->storedSettings();
        $settings['webhooks']['items'] = [[
            'url'              => 'https://hooks.example.com/a',
            'enabled'          => true,
            'method'           => 'POST',
            'events'           => ['login'],
            'headers'          => [['key' => 'Authorization', 'value' => 'Bearer secret']],
            'payload_template' => base64_encode('{"user":"{{email}}"}'),
            'timeout'          => 5,
        ]];

        return $settings;
    }

    public function testListWebhooksDecodesTemplateAndMasksHeaders()
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->settingsWithWebhook()));

        $result = (new ListWebhooksAbility($settings))->execute([]);

        $this->assertTrue($result['enabled']);
        $this->assertCount(0, (new ListWebhooksAbility($settings))->execute(['event' => 'register'])['items']);
        $this->assertCount(1, (new ListWebhooksAbility($settings))->execute(['event' => 'login'])['items']);
        $this->assertCount(1, $result['items']);
        $this->assertSame(0, $result['items'][0]['index']);
        $this->assertSame('{"user":"{{email}}"}', $result['items'][0]['payload_template']);
        $this->assertSame(SettingsRedactor::MASK, $result['items'][0]['headers'][0]['value']);
    }

    public function testAddWebhookAppendsEncodedEntryAndKeepsExistingOnes()
    {
        $stored        = $this->settingsWithWebhook();
        $wordPressData = $this->makeWordPressDataMock($stored);

        $saved = null;
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->willReturnCallback(function () use (&$saved) {
                $saved = json_decode(func_get_arg(1), true);
                return true;
            });

        $settings = $this->makeSettings($wordPressData);
        $result   = (new AddWebhookAbility($settings))->execute([
            'url'              => 'https://hooks.example.com/b',
            'events'           => ['register', 'not-an-event'],
            'method'           => 'put',
            'headers'          => [['key' => 'X-Token', 'value' => 'abc']],
            'payload_template' => '{"id":"{{id}}"}',
        ]);

        $this->assertSame(1, $result['index']);
        $this->assertSame(SettingsRedactor::MASK, $result['headers'][0]['value']);
        $this->assertCount(2, $saved['webhooks']['items']);
        $this->assertSame($stored['webhooks']['items'][0], $saved['webhooks']['items'][0]);
        $this->assertSame([
            'url'              => 'https://hooks.example.com/b',
            'enabled'          => true,
            'method'           => 'PUT',
            'events'           => ['register'],
            'headers'          => [['key' => 'X-Token', 'value' => 'abc']],
            'payload_template' => base64_encode('{"id":"{{id}}"}'),
            'timeout'          => 0,
        ], $saved['webhooks']['items'][1]);
        $this->assertSame($stored['general']['decryption_key'], $saved['general']['decryption_key']);
    }

    /**
     * @return array<string, array{0: array, 1: int|null, 2: string|null}>
     */
    public static function invalidWebhookProvider()
    {
        return [
            'no valid event' => [
                ['url' => 'https://hooks.example.com', 'events' => ['nope']],
                ErrorCodes::ERR_ABILITY_INVALID_WEBHOOK,
                null,
            ],
            'empty url' => [
                ['url' => '', 'events' => ['login']],
                ErrorCodes::ERR_ABILITY_INVALID_WEBHOOK,
                null,
            ],
            'plain http rejected by settings validation' => [
                ['url' => 'http://hooks.example.com', 'events' => ['login']],
                null,
                'Webhook #2: only HTTPS URLs are allowed.',
            ],
        ];
    }

    #[DataProvider('invalidWebhookProvider')]
    public function testAddWebhookRejectsInvalidInput($input, $expectedCode, $expectedMessage)
    {
        $wordPressData = $this->makeWordPressDataMock($this->settingsWithWebhook());
        $wordPressData->expects($this->never())->method('updateOption');

        $this->expectException(Exception::class);
        if ($expectedCode !== null) {
            $this->expectExceptionCode($expectedCode);
        }
        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        (new AddWebhookAbility($this->makeSettings($wordPressData)))->execute($input);
    }

    public function testRemoveWebhook()
    {
        $wordPressData = $this->makeWordPressDataMock($this->settingsWithWebhook());
        $saved         = null;
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->willReturnCallback(function () use (&$saved) {
                $saved = json_decode(func_get_arg(1), true);
                return true;
            });

        $result = (new RemoveWebhookAbility($this->makeSettings($wordPressData)))->execute([
            'index' => 0,
            'url'   => 'https://hooks.example.com/a',
        ]);

        $this->assertSame('https://hooks.example.com/a', $result['url']);
        $this->assertSame([], $saved['webhooks']['items']);
    }

    public function testWebhookUrlQueryIsMaskedAndMaskedUrlStillRemoves()
    {
        $stored = $this->settingsWithWebhook();
        $stored['webhooks']['items'][0]['url'] = 'https://hooks.example.com/a?token=s3cret&flag';
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));

        $listed = (new ListWebhooksAbility($settings))->execute([])['items'][0]['url'];
        $this->assertSame('https://hooks.example.com/a?token=' . SettingsRedactor::MASK . '&flag', $listed);

        $wordPressData = $this->makeWordPressDataMock($stored);
        $wordPressData->expects($this->once())->method('updateOption')->willReturn(true);
        $ability = new RemoveWebhookAbility($this->makeSettings($wordPressData));
        $result  = $ability->execute(['index' => 0, 'url' => $listed]);

        $this->assertSame($listed, $result['url']);
        $this->assertSame(['url' => $listed, 'index' => 0], $ability->auditDetails(['url' => $listed], $result));
    }

    public function testListWebhooksShowsSecretsOnlyWithViewSecretsPermission()
    {
        $stored = $this->settingsWithWebhook();
        $stored['webhooks']['items'][0]['url'] = 'https://hooks.example.com/a?token=s3cret';
        $stored['integrations']['3rdparty']['ai_abilities'] = ['enabled' => true, 'access' => 'read', 'allow_view_secrets' => true];

        $item = (new ListWebhooksAbility($this->makeSettings($this->makeWordPressDataStub($stored))))->execute([])['items'][0];

        $this->assertSame('https://hooks.example.com/a?token=s3cret', $item['url']);
        $this->assertSame('Bearer secret', $item['headers'][0]['value']);
    }

    public function testAddWebhookAuditMasksUrlQuery()
    {
        $ability = new AddWebhookAbility($this->makeSettings($this->makeWordPressDataStub($this->storedSettings())));

        $this->assertSame(
            ['url' => 'https://hooks.example.com/b?key=' . SettingsRedactor::MASK, 'index' => 1],
            $ability->auditDetails(['url' => 'https://hooks.example.com/b?key=abc'], ['index' => 1])
        );
    }

    /**
     * @return array<string, array{0: array}>
     */
    public static function mismatchedRemoveProvider()
    {
        return [
            'url does not match index' => [['index' => 0, 'url' => 'https://other.example.com']],
            'index out of range'       => [['index' => 5, 'url' => 'https://hooks.example.com/a']],
        ];
    }

    #[DataProvider('mismatchedRemoveProvider')]
    public function testRemoveWebhookRequiresMatchingIndexAndUrl($input)
    {
        $wordPressData = $this->makeWordPressDataMock($this->settingsWithWebhook());
        $wordPressData->expects($this->never())->method('updateOption');

        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_ABILITY_WEBHOOK_NOT_FOUND);

        (new RemoveWebhookAbility($this->makeSettings($wordPressData)))->execute($input);
    }
}
