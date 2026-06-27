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
        $this->wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $this->logRepoMock       = $this->createStub(WebhookLogRepositoryInterface::class);
    }

    private function makeSettings(array $webhooks): SimpleJWTLoginSettings
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        return new SimpleJWTLoginSettings($this->wordPressDataMock);
    }

    public function testDispatchWithNoWebhooksDoesNotThrow()
    {
        $this->expectNotToPerformAssertions();
        $service = new WebhooksService($this->makeSettings([]));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchWithDisabledWebhookDoesNotThrow()
    {
        $this->expectNotToPerformAssertions();
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => false, 'events' => ['login']],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchWithEnabledWebhookDoesNotThrow()
    {
        $this->expectNotToPerformAssertions();
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchWithCustomMethodDoesNotThrow()
    {
        $this->expectNotToPerformAssertions();
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'PUT', 'events' => ['login'], 'headers' => []],
        ];
        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchWithCustomHeadersDoesNotThrow()
    {
        $this->expectNotToPerformAssertions();
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
        $this->expectNotToPerformAssertions();
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
    }

    public function testDispatchWithEmptyPayloadTemplateUsesDefaultPayload()
    {
        $this->expectNotToPerformAssertions();
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
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
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
        $service->runPendingJobs();
    }

    public function testDispatchDoesNotLogWhenNoRepository()
    {
        $this->expectNotToPerformAssertions();
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        $service = new WebhooksService($this->makeSettings($webhooks));
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $service->runPendingJobs();
    }

    public function testDispatchLogsOncePerWebhook()
    {
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
        $webhooks = [
            ['url' => 'https://hook1.example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
            ['url' => 'https://hook2.example.com', 'enabled' => true, 'method' => 'GET',  'events' => ['login'], 'headers' => []],
        ];

        $this->logRepoMock->expects($this->exactly(2))->method('insert');

        $service = new WebhooksService($this->makeSettings($webhooks), $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $service->runPendingJobs();
    }

    public function testDispatchDoesNotLogDisabledWebhooks()
    {
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
        $webhooks = [
            ['url' => 'https://enabled.example.com',  'enabled' => true,  'method' => 'POST', 'events' => ['login'], 'headers' => []],
            ['url' => 'https://disabled.example.com', 'enabled' => false, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        $this->logRepoMock->expects($this->exactly(1))->method('insert');

        $service = new WebhooksService($this->makeSettings($webhooks), $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $service->runPendingJobs();
    }

    public function testDispatchDefersWorkUntilShutdownAndDoesNotLogInline()
    {
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        // On PHP-FPM the deferral must be registered on the WordPress `shutdown`
        // hook, and no log write may happen inline during dispatch().
        $this->wordPressDataMock = $this->createMock(WordPressDataInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        $this->wordPressDataMock->method('canFinishRequest')->willReturn(true);
        $this->wordPressDataMock->expects($this->once())
            ->method('addAction')
            ->with('shutdown', $this->anything());
        $this->logRepoMock->expects($this->never())->method('insert');

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);
        $service     = new WebhooksService($jwtSettings, $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testRunPendingJobsFlushesResponseThenLogsQueuedWebhooks()
    {
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        $this->wordPressDataMock = $this->createMock(WordPressDataInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        $this->wordPressDataMock->method('canFinishRequest')->willReturn(true);
        $this->wordPressDataMock->expects($this->once())->method('finishRequest');
        $this->logRepoMock->expects($this->once())->method('insert');

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);
        $service     = new WebhooksService($jwtSettings, $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $service->runPendingJobs();
    }

    public function testDispatchProcessesInlineWhenResponseCannotBeFlushed()
    {
        $this->logRepoMock = $this->createMock(WebhookLogRepositoryInterface::class);
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login'], 'headers' => []],
        ];

        // No PHP-FPM: webhooks run inline during dispatch(), no shutdown hook.
        $this->wordPressDataMock = $this->createMock(WordPressDataInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        $this->wordPressDataMock->method('canFinishRequest')->willReturn(false);
        $this->wordPressDataMock->expects($this->never())->method('addAction');
        $this->logRepoMock->expects($this->once())->method('insert');

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);
        $service     = new WebhooksService($jwtSettings, $this->logRepoMock);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
    }

    public function testDispatchRegistersShutdownHookOnlyOnceForMultipleEvents()
    {
        $webhooks = [
            ['url' => 'https://example.com', 'enabled' => true, 'method' => 'POST', 'events' => ['login', 'auth'], 'headers' => []],
        ];

        $this->wordPressDataMock = $this->createMock(WordPressDataInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['webhooks' => $webhooks]));
        $this->wordPressDataMock->method('canFinishRequest')->willReturn(true);
        $this->wordPressDataMock->expects($this->once())
            ->method('addAction')
            ->with('shutdown', $this->anything());

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);
        $service     = new WebhooksService($jwtSettings);
        $service->dispatch(WebhooksSettings::EVENT_LOGIN, ['user_id' => 1]);
        $service->dispatch(WebhooksSettings::EVENT_AUTH, ['user_id' => 1]);
    }
}
