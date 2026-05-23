<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Oauth\GithubOauthSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class GithubOauthSettingsTest extends TestCase
{
    private function make(): GithubOauthSettings
    {
        return new GithubOauthSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);

        return $stub;
    }

    public function testGetGroup(): void
    {
        $this->assertSame('github', $this->make()->getGroup());
    }

    public function testGetName(): void
    {
        $this->assertSame('GitHub', $this->make()->getName());
    }

    public static function isExchangeTokenEnabledProvider(): array
    {
        return [
            'enabled'  => [['enable_exchange_token' => true], true],
            'disabled' => [['enable_exchange_token' => false], false],
            'missing'  => [[], false],
        ];
    }

    #[DataProvider('isExchangeTokenEnabledProvider')]
    public function testIsExchangeTokenEnabled(array $data, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->isExchangeTokenEnabled());
    }

    public function testProcessPostIncludesEnableExchangeTokenField(): void
    {
        $post   = ['github' => ['enable_exchange_token' => '1']];
        $result = $this->make()->processPost($post, $this->wpData());

        $this->assertArrayHasKey('enable_exchange_token', $result);
        $this->assertSame(true, $result['enable_exchange_token']);
    }

    public function testProcessPostExchangeTokenDefaultsFalseWhenMissing(): void
    {
        $result = $this->make()->processPost(['github' => []], $this->wpData());

        $this->assertSame(false, $result['enable_exchange_token']);
    }

    public function testValidatePassesWhenNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate(['github' => []]);
    }

    public function testValidatePassesWhenAllValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'github' => [
                'enabled'       => 1,
                'enable_oauth'  => 1,
                'client_id'     => 'gh-client-id',
                'client_secret' => 'gh-client-secret',
            ],
        ]);
    }

    public function testValidatePassesWithOnlyExchangeTokenEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'github' => [
                'enabled'               => 1,
                'enable_exchange_token' => 1,
                'client_id'             => 'gh-client-id',
                'client_secret'         => 'gh-client-secret',
            ],
        ]);
    }

    // generateCode(PREFIX_APPLICATIONS=12, errorCode) = 12000 + errorCode
    public static function validateThrowsProvider(): array
    {
        return [
            'no features enabled'   => [['enabled' => 1], 12015],
            'client_id missing'     => [['enabled' => 1, 'enable_oauth' => 1], 12016],
            'client_secret missing' => [['enabled' => 1, 'enable_oauth' => 1, 'client_id' => 'gh-id'], 12017],
            'redirect_uri missing'  => [
                ['enabled' => 1, 'enable_exchange_code' => 1, 'client_id' => 'gh-id', 'client_secret' => 'gh-secret'],
                12018,
            ],
        ];
    }

    #[DataProvider('validateThrowsProvider')]
    public function testValidateThrows(array $groupPost, int $expectedCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedCode);
        $this->make()->validate(['github' => $groupPost]);
    }
}
