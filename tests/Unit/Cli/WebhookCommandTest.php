<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\WebhookCommand;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_CLI;

class WebhookCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
        RecordingAuditLog::$records = [];
    }

    /**
     * @param mixed $response
     */
    #[DataProvider('responseProvider')]
    public function testCallsTheWebhookAndAuditsTheOutcome($response, array $expectedErrors, array $expectedSuccesses, string $expectedStatus): void
    {
        $command = $this->makeCommand($response);
        $command->test(['0'], []);

        // A disabled webhook is called too, with its first event.
        $this->assertSame([[true, 'https://example.com', 'login']], $command->sent);
        $this->assertSame($expectedErrors, WP_CLI::$errors);
        $this->assertSame($expectedSuccesses, WP_CLI::$successes);
        $this->assertSame([$expectedStatus], array_column(RecordingAuditLog::$records, 'status'));
    }

    public static function responseProvider(): array
    {
        return [
            '2xx'          => [['response' => ['code' => 204], 'body' => ''], [], ['The webhook answered with HTTP 204.'], 'success'],
            'non 2xx'      => [['response' => ['code' => 500], 'body' => 'boom'], ['The webhook answered with HTTP 500.'], [], 'failure'],
            'no url'       => [null, ['The webhook has no URL.'], [], 'failure'],
        ];
    }

    public function testUnknownIndex(): void
    {
        $this->makeCommand(null)->test(['3'], []);

        $this->assertSame(['Webhook not found. See wp jwt webhook list.'], WP_CLI::$errors);
        $this->assertSame([], RecordingAuditLog::$records);
    }

    /**
     * @param mixed $response
     */
    private function makeCommand($response): WebhookCommand
    {
        $jwtSettings = $this->createStub(SimpleJWTLoginSettings::class);
        $jwtSettings->method('getWebhooksSettings')->willReturn((new WebhooksSettings())->withSettings(['webhooks' => ['items' => [
            ['url' => 'https://example.com', 'events' => ['login'], 'enabled' => false],
        ]]]));

        return new class ($jwtSettings, $response) extends WebhookCommand {
            /** @var mixed */
            private $jwtSettings;
            /** @var mixed */
            private $response;
            /** @var array */
            public $sent = [];

            public function __construct($jwtSettings, $response)
            {
                $this->jwtSettings = $jwtSettings;
                $this->response = $response;
            }

            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            protected function createSettings(): SimpleJWTLoginSettings
            {
                return $this->jwtSettings;
            }

            protected function sendWebhook(SimpleJWTLoginSettings $jwtSettings, array $webhook, $event)
            {
                $this->sent[] = [$jwtSettings === $this->jwtSettings, $webhook['url'], $event];
                return $this->response;
            }
        };
    }
}
