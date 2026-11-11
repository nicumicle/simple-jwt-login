<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use WP_REST_Response;

/**
 * Shared helpers: builds SimpleJWTLoginSettings from the real stored-settings
 * fixture with a configurable AI access mode.
 */
abstract class AbilityTestCase extends TestCase
{
    /**
     * @param array $overrides Top-level sections merged into the fixture.
     * @return array
     */
    protected function storedSettings(array $overrides = [])
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../Settings/fixtures/stored-settings.json'),
            true
        );

        return array_replace_recursive($fixture, $overrides);
    }

    /**
     * Mock: use only when the test sets expectations on it.
     *
     * @param array $storedSettings
     * @return WordPressDataInterface&MockObject
     */
    protected function makeWordPressDataMock(array $storedSettings)
    {
        return $this->configureWordPressData($this->createMock(WordPressDataInterface::class), $storedSettings);
    }

    /**
     * @param array      $storedSettings
     * @param array|null $freshSettings  Successive values returned by getFreshOption()
     *                                   (defaults to $storedSettings on every call).
     * @param array|null $capabilities   Capability => granted for the current user
     *                                   (null = administrator: every capability granted).
     * @return WordPressDataInterface&Stub
     */
    protected function makeWordPressDataStub(array $storedSettings, $freshSettings = null, $capabilities = null)
    {
        return $this->configureWordPressData(
            $this->createStub(WordPressDataInterface::class),
            $storedSettings,
            $freshSettings,
            $capabilities
        );
    }

    /**
     * @param array      $storedSettings
     * @param array|null $capabilities See makeWordPressDataStub().
     * @return WordPressDataInterface&MockObject
     */
    protected function makeWordPressDataMockForUser(array $storedSettings, $capabilities)
    {
        return $this->configureWordPressData(
            $this->createMock(WordPressDataInterface::class),
            $storedSettings,
            null,
            $capabilities
        );
    }

    /**
     * Capabilities of the users the non-administrator tests run as.
     *
     * @return array<string, array<string, bool>|null>
     */
    public static function userRoles()
    {
        return [
            'administrator' => null,
            'editor'        => ['read' => true, 'edit_posts' => true, 'edit_others_posts' => true, 'publish_posts' => true],
            'subscriber'    => ['read' => true],
            'logged out'    => [],
        ];
    }

    /**
     * @param Stub       $wordPressData
     * @param array      $storedSettings
     * @param array|null $freshSettings
     * @param array|null $capabilities
     * @return Stub
     */
    private function configureWordPressData($wordPressData, array $storedSettings, $freshSettings = null, $capabilities = null)
    {
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode($storedSettings));
        $freshValues = array_map('json_encode', $freshSettings !== null ? $freshSettings : [$storedSettings]);
        $wordPressData->method('getFreshOption')->willReturnCallback(function () use (&$freshValues) {
            return count($freshValues) > 1 ? array_shift($freshValues) : $freshValues[0];
        });
        $wordPressData->method('sanitizeTextField')->willReturnCallback(function ($value) {
            return trim(strip_tags((string) $value));
        });
        $wordPressData->method('ksesPost')->willReturnCallback(function ($value) {
            return (string) $value;
        });
        $wordPressData->method('roleExists')->willReturn(true);
        $wordPressData->method('parseUrl')->willReturnCallback('parse_url');
        $loggedIn = $capabilities === null || !empty($capabilities);
        $wordPressData->method('isUserLoggedIn')->willReturn($loggedIn);
        $wordPressData->method('currentUserCan')->willReturnCallback(function ($capability) use ($capabilities) {
            return $capabilities === null || !empty($capabilities[$capability]);
        });
        $wordPressData->method('getCurrentUserId')->willReturn($loggedIn ? 1 : 0);
        $wordPressData->method('isAbilitiesApiAvailable')->willReturn(true);
        $wordPressData->method('createResponse')->willReturnCallback(function ($body) {
            return new WP_REST_Response($body);
        });

        return $wordPressData;
    }

    /**
     * @param WordPressDataInterface $wordPressData
     * @return SimpleJWTLoginSettings
     */
    protected function makeSettings($wordPressData)
    {
        return new SimpleJWTLoginSettings($wordPressData);
    }
}
