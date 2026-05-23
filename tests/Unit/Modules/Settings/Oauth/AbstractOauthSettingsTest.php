<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AbstractOauthSettingsTest extends TestCase
{
    private function make(): ConcreteTestOauthSettings
    {
        return new ConcreteTestOauthSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);

        return $stub;
    }

    public function testWithSettingsReturnsThis(): void
    {
        $settings = $this->make();
        $this->assertSame($settings, $settings->withSettings([]));
    }

    public static function isEnabledProvider(): array
    {
        return [
            'enabled truthy'  => [['enabled' => 1], true],
            'enabled falsy'   => [['enabled' => 0], false],
            'enabled missing' => [[], false],
        ];
    }

    #[DataProvider('isEnabledProvider')]
    public function testIsEnabled(array $data, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->isEnabled());
    }

    public static function stringGetterProvider(): array
    {
        return [
            'client_id set'           => ['client_id', 'my-id', 'getClientId', 'my-id'],
            'client_id missing'       => ['client_id', null, 'getClientId', ''],
            'client_secret set'       => ['client_secret', 'my-secret', 'getClientSecret', 'my-secret'],
            'client_secret missing'   => ['client_secret', null, 'getClientSecret', ''],
            'redirect_uri set'        => ['redirect_uri_exchange_code', 'https://example.com/cb', 'getExchangeCodeRedirectUri', 'https://example.com/cb'],
            'redirect_uri missing'    => ['redirect_uri_exchange_code', null, 'getExchangeCodeRedirectUri', ''],
        ];
    }

    #[DataProvider('stringGetterProvider')]
    public function testStringGetters($key, $value, $method, $expected): void
    {
        $data   = $value !== null ? [$key => $value] : [];
        $result = $this->make()->withSettings($data)->$method();
        $this->assertSame($expected, $result);
    }

    public static function boolGetterProvider(): array
    {
        return [
            'allow_on_all_endpoints true'         => ['allow_on_all_endpoints', true, 'isAllowedOnAllEndpoints', true],
            'allow_on_all_endpoints false'         => ['allow_on_all_endpoints', false, 'isAllowedOnAllEndpoints', false],
            'allow_on_all_endpoints missing'       => ['allow_on_all_endpoints', null, 'isAllowedOnAllEndpoints', false],
            'create_user_if_not_exists true'       => ['create_user_if_not_exists', true, 'isCreateUserIfNotExistsEnabled', true],
            'create_user_if_not_exists missing'    => ['create_user_if_not_exists', null, 'isCreateUserIfNotExistsEnabled', false],
            'enable_oauth true'                    => ['enable_oauth', true, 'isOauthEnabled', true],
            'enable_oauth missing'                 => ['enable_oauth', null, 'isOauthEnabled', false],
            'enable_exchange_code true'            => ['enable_exchange_code', true, 'isExchangeCodeEnabled', true],
            'enable_exchange_code missing'         => ['enable_exchange_code', null, 'isExchangeCodeEnabled', false],
        ];
    }

    #[DataProvider('boolGetterProvider')]
    public function testBoolGetters($key, $value, $method, bool $expected): void
    {
        $data   = $value !== null ? [$key => $value] : [];
        $result = $this->make()->withSettings($data)->$method();
        $this->assertSame($expected, $result);
    }

    public static function getProvider(): array
    {
        return [
            'key present'              => ['extra_key', 'extra_val', 'extra_key', '', 'extra_val'],
            'key missing no default'   => ['extra_key', null, 'other_key', '', ''],
            'key missing with default' => ['extra_key', null, 'other_key', 'fallback', 'fallback'],
        ];
    }

    #[DataProvider('getProvider')]
    public function testGet($dataKey, $dataValue, $queryKey, $default, $expected): void
    {
        $data   = $dataValue !== null ? [$dataKey => $dataValue] : [];
        $result = $this->make()->withSettings($data)->get($queryKey, $default);
        $this->assertSame($expected, $result);
    }

    public static function isFieldEnabledProvider(): array
    {
        return [
            'field truthy'  => ['my_flag', true, true],
            'field falsy'   => ['my_flag', false, false],
            'field missing' => ['my_flag', null, false],
        ];
    }

    #[DataProvider('isFieldEnabledProvider')]
    public function testIsFieldEnabled($key, $value, bool $expected): void
    {
        $data   = $value !== null ? [$key => $value] : [];
        $result = $this->make()->withSettings($data)->isFieldEnabled($key);
        $this->assertSame($expected, $result);
    }

    public function testValidateDoesNothingWhenNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate(['test' => []]);
    }

    public function testValidateDoesNothingWhenGroupMissing(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([]);
    }

    // generateCode(PREFIX_APPLICATIONS=12, errorCode) = 12000 + errorCode
    public static function validateThrowsProvider(): array
    {
        return [
            'no features enabled'  => [['enabled' => 1], 12099],
            'client_id missing'    => [['enabled' => 1, 'enable_oauth' => 1], 12100],
            'redirect_uri missing' => [['enabled' => 1, 'enable_exchange_code' => 1, 'client_id' => 'id'], 12101],
        ];
    }

    #[DataProvider('validateThrowsProvider')]
    public function testValidateThrows(array $groupPost, int $expectedCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedCode);
        $this->make()->validate(['test' => $groupPost]);
    }

    public function testValidatePassesWhenAllRequiredFieldsPresent(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'test' => [
                'enabled'    => 1,
                'enable_oauth' => 1,
                'client_id'  => 'my-id',
            ],
        ]);
    }

    public function testValidatePassesWithExchangeCodeAndRedirectUri(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'test' => [
                'enabled'                    => 1,
                'enable_exchange_code'       => 1,
                'client_id'                  => 'my-id',
                'redirect_uri_exchange_code' => 'https://example.com/cb',
            ],
        ]);
    }

    public function testProcessPostReturnsDefaultsForMissingFields(): void
    {
        $result = $this->make()->processPost(['test' => []], $this->wpData());

        $this->assertSame(0, $result['enabled']);
        $this->assertSame('', $result['client_id']);
        $this->assertSame('', $result['client_secret']);
        $this->assertSame(false, $result['allow_on_all_endpoints']);
        $this->assertSame(false, $result['create_user_if_not_exists']);
        $this->assertSame(false, $result['enable_oauth']);
        $this->assertSame(false, $result['enable_exchange_code']);
        $this->assertSame('', $result['redirect_uri_exchange_code']);
    }

    public function testProcessPostReturnsDefaultsWhenGroupMissing(): void
    {
        $result = $this->make()->processPost([], $this->wpData());

        $this->assertSame(0, $result['enabled']);
        $this->assertSame('', $result['client_id']);
        $this->assertSame(false, $result['enable_oauth']);
    }

    public function testProcessPostCastsValues(): void
    {
        $post = [
            'test' => [
                'enabled'                    => '1',
                'client_id'                  => 'my-client-id',
                'client_secret'              => 'my-secret',
                'allow_on_all_endpoints'     => '1',
                'create_user_if_not_exists'  => '1',
                'enable_oauth'               => '1',
                'enable_exchange_code'       => '1',
                'redirect_uri_exchange_code' => 'https://example.com/cb',
            ],
        ];

        $result = $this->make()->processPost($post, $this->wpData());

        $this->assertSame(1, $result['enabled']);
        $this->assertSame('my-client-id', $result['client_id']);
        $this->assertSame('my-secret', $result['client_secret']);
        $this->assertSame(true, $result['allow_on_all_endpoints']);
        $this->assertSame(true, $result['create_user_if_not_exists']);
        $this->assertSame(true, $result['enable_oauth']);
        $this->assertSame(true, $result['enable_exchange_code']);
        $this->assertSame('https://example.com/cb', $result['redirect_uri_exchange_code']);
    }
}
