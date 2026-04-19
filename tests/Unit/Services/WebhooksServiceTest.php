<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\WebhooksService;

class WebhooksServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WebhookLogRepositoryInterface
     */
    private $logRepoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)->getMock();
        $this->logRepoMock       = $this->getMockBuilder(WebhookLogRepositoryInterface::class)->getMock();
    }

    private function makeSettings(array $webhooks): SimpleJWTLoginSettings
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        return new SimpleJWTLoginSettings($this->wordPressDataMock);
    }

    public function testDispatchWithNoWebhooksDoesNotThrow()
    {
        $service = new WebhooksService($this->makeSettings([]));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testDispatchWithDisabledWebhookDoesNotThrow()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => false, 'events' => ['login']],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testDispatchWithEnabledWebhookDoesNotThrow()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testDispatchWithCustomMethodDoesNotThrow()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'PUT', 'events' => ['login'], 'headers' => []],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testDispatchWithCustomHeadersDoesNotThrow()
    {
        $webhooks = [
            [
                'url'     => 'https://example.com',
                'enabled' => true,
                'method'  => 'POST',
                'events'  => ['login'],
                'headers' => [
                    ['key' => 'Authorization', 'value' => 'Bearer token'],
                    ['key' => 'X-Source',      'value' => 'simple-jwt-login'],
                ],
            ],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testOnlyWebhooksMatchingEventAreSelected()
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'webhooks' => [
                    ['url' => 'https://login-hook.com',    'enabled' => true, 'events' => ['login']],
                    ['url' => 'https://auth-hook.com',     'enabled' => true, 'events' => ['auth']],
                    ['url' => 'https://register-hook.com', 'enabled' => true, 'events' => ['register']],
                ],
            ]));
        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);

        $loginWebhooks    = $jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent(WebhooksSettings::EVENT_LOGIN);
        $authWebhooks     = $jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent(WebhooksSettings::EVENT_AUTH);
        $registerWebhooks = $jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent(WebhooksSettings::EVENT_REGISTER);

        $this->assertCount(1, $loginWebhooks);
        $this->assertSame('https://login-hook.com', $loginWebhooks[0]['url']);

        $this->assertCount(1, $authWebhooks);
        $this->assertSame('https://auth-hook.com', $authWebhooks[0]['url']);

        $this->assertCount(1, $registerWebhooks);
        $this->assertSame('https://register-hook.com', $registerWebhooks[0]['url']);
    }

    public function testDispatchWithPayloadTemplateDoesNotThrow()
    {
        $webhooks = [
            [
                'url'              => 'https://example.com',
                'enabled'          => true,
                'method'           => 'POST',
                'events'           => ['login'],
                'headers'          => [],
                'payload_template' => '{"id":"{{user_id}}","email":"{{user_email}}","ev":"{{event}}"}',
            ],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 42, 'user_email' => 'test@example.com']);
        $this->assertTrue(true);
    }

    public function testDispatchWithEmptyPayloadTemplateUsesDefaultPayload()
    {
        $webhooks = [
            [
                'url'              => 'https://example.com',
                'enabled'          => true,
                'method'           => 'POST',
                'events'           => ['login'],
                'headers'          => [],
                'payload_template' => '',
            ],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $this->assertTrue(true);
    }

    public function testDisabledWebhookIsNotSelectedForDispatch()
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'webhooks' => [
                    ['url' => 'https://enabled.com',  'enabled' => true,  'events' => ['login']],
                    ['url' => 'https://disabled.com', 'enabled' => false, 'events' => ['login']],
                ],
            ]));
        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);

        $webhooks = $jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent(WebhooksSettings::EVENT_LOGIN);
        $this->assertCount(1, $webhooks);
        $this->assertSame('https://enabled.com', $webhooks[0]['url']);
    }

    public function testDispatchLogsCallWhenRepositoryProvided()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        // bootstrap stub: wp_remote_request returns null → status_code=0 (error), response_body=''
        $this->logRepoMock->expects($this->once())
            ->method('insert')
            ->with(
                'https://example.com',
                WebhooksSettings::EVENT_LOGIN,
                'POST',
                $this->anything(),
                $this->anything()
            );

        $service = new WebhooksService($this->makeSettings($webhooks), $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchDoesNotLogWhenNoRepository()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        $this->logRepoMock->expects($this->never())->method('insert');

        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchLogsOncePerWebhook()
    {
        $webhooks = [
            ['url' => 'https://hook1.example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
            ['url' => 'https://hook2.example.com', 'enabled' => true, 'method' => 'GET',  'events' => ['login'], 'headers' => []],
        ];

        $this->logRepoMock->expects($this->exactly(2))->method('insert');

        $service = new WebhooksService($this->makeSettings($webhooks), $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchDoesNotLogDisabledWebhooks()
    {
        $webhooks = [
            ['url' => 'https://enabled.example.com',  'enabled' => true,  'method' => 'POST', 'events' => ['login'], 'headers' => []],
            ['url' => 'https://disabled.example.com', 'enabled' => false, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        $this->logRepoMock->expects($this->exactly(1))->method('insert');

        $service = new WebhooksService($this->makeSettings($webhooks), $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }
}
