<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;

/**
 * A JWT Rule that shares the plugin's key maps its own claim to a user. A client must not
 * set that claim (or a payload claim that makes the rule match) in the JWT the plugin signs.
 */
class RuleIdentityClaimsTest extends TestCase
{
    /**
     * @return array<string, array{array, array, array}>
     */
    public static function clientClaimsProvider()
    {
        $idRule = [
            'condition_type'     => 'iss',
            'condition_value'    => 'partner',
            'login_by'           => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'login_by_parameter' => 'sub',
        ];

        return [
            'rule identity claim' => [[$idRule], ['sub' => 1], ['sub']],
            'nested rule identity claim' => [
                [array_merge($idRule, ['login_by_parameter' => 'data.uid'])],
                ['data' => ['uid' => 1, 'note' => 'kept']],
                ['data.uid'],
            ],
            'payload claim a rule matches on' => [
                [array_merge($idRule, ['condition_type' => 'payload', 'condition_key' => 'tenant'])],
                ['tenant' => 'partner'],
                ['tenant'],
            ],
        ];
    }

    #[DataProvider('clientClaimsProvider')]
    public function testClientCannotSetRuleClaims(array $rules, array $clientPayload, array $strippedPaths)
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode([
            '_schema_version' => 2,
            'general'         => ['decryption_key' => '123'],
            'jwt_rules'       => ['rules' => $rules],
        ]));
        $settings = new SimpleJWTLoginSettings($wordPressData);

        $payload = AuthenticateService::generatePayload(
            $clientPayload + ['favorite_color' => 'blue'],
            $wordPressData,
            $settings,
            'user'
        );

        foreach ($strippedPaths as $path) {
            $this->assertNull($this->claimAt($payload, $path), $path . ' must not come from the client');
        }
        $this->assertSame('blue', $payload['favorite_color']);
        if (isset($clientPayload['data']['note'])) {
            $this->assertSame('kept', $payload['data']['note']);
        }
    }

    /**
     * @param array $payload
     * @param string $path
     * @return mixed
     */
    private function claimAt(array $payload, $path)
    {
        foreach (explode('.', $path) as $segment) {
            if (!is_array($payload) || !array_key_exists($segment, $payload)) {
                return null;
            }
            $payload = $payload[$segment];
        }

        return $payload;
    }
}
