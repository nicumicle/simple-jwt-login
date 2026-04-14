<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AuditLogSettingsTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)->getMock();
        $this->wordPressDataMock->method('sanitizeTextField')->willReturnArgument(0);
    }

    private function buildSettings(array $settings = [])
    {
        return (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings($settings);
    }

    public function testDefaultsWhenNoSettings()
    {
        $settings = $this->buildSettings();

        $this->assertFalse($settings->isEnabled());
        $this->assertSame(AuditLogSettings::DEFAULT_RETENTION_DAYS, $settings->getRetentionDays());
        $this->assertSame([], $settings->getEnabledEvents());
    }

    public function testIsEnabledReturnsTrueWhenSet()
    {
        $settings = $this->buildSettings([
            'audit_log' => ['enabled' => true],
        ]);

        $this->assertTrue($settings->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenExplicitlyFalse()
    {
        $settings = $this->buildSettings([
            'audit_log' => ['enabled' => false],
        ]);

        $this->assertFalse($settings->isEnabled());
    }

    public function testIsEventEnabledReturnsTrueForListedEvent()
    {
        $settings = $this->buildSettings([
            'audit_log' => [
                'enabled'        => true,
                'enabled_events' => [AuditEvents::AUTH_LOGIN_SUCCESS],
            ],
        ]);

        $this->assertTrue($settings->isEventEnabled(AuditEvents::AUTH_LOGIN_SUCCESS));
        $this->assertFalse($settings->isEventEnabled(AuditEvents::AUTH_LOGIN_FAILED));
    }

    public function testGetRetentionDaysReturnsStoredValue()
    {
        $settings = $this->buildSettings([
            'audit_log' => ['retention_days' => 30],
        ]);

        $this->assertSame(30, $settings->getRetentionDays());
    }

    public function testInitSettingsFromPostSetsEnabled()
    {
        $post = [
            'audit_log' => [
                'enabled'        => '1',
                'enabled_events' => [AuditEvents::AUTH_LOGIN_SUCCESS, AuditEvents::AUTH_LOGIN_FAILED],
                'retention_days' => '60',
            ],
        ];

        $settings = (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        $settings->initSettingsFromPost();
        $result = $settings->getSettings();

        $this->assertTrue((bool) $result['audit_log']['enabled']);
        $this->assertContains(AuditEvents::AUTH_LOGIN_SUCCESS, $result['audit_log']['enabled_events']);
        $this->assertSame(60, $result['audit_log']['retention_days']);
    }

    public function testInitSettingsFromPostDefaultsRetentionWhenMissing()
    {
        $post = ['audit_log' => ['enabled' => '1']];

        $settings = (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        $settings->initSettingsFromPost();
        $result = $settings->getSettings();

        $this->assertSame(
            AuditLogSettings::DEFAULT_RETENTION_DAYS,
            $result['audit_log']['retention_days']
        );
    }

    public function testValidateSettingsThrowsOnZeroRetention()
    {
        $this->expectException(Exception::class);

        $post = [
            'audit_log' => [
                'enabled'        => '1',
                'retention_days' => '0',
            ],
        ];

        $settings = (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        $settings->validateSettings();
    }

    public function testValidateSettingsPassesForValidRetention()
    {
        $post = [
            'audit_log' => [
                'enabled'        => '1',
                'retention_days' => '30',
            ],
        ];

        $settings = (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        // Should not throw
        $settings->validateSettings();
        $this->assertTrue(true);
    }

    public function testValidateSettingsSkipsWhenNoPostGroup()
    {
        $settings = (new AuditLogSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost([]);

        // Should not throw when audit_log not in POST
        $settings->validateSettings();
        $this->assertTrue(true);
    }
}
