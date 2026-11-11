<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Scored security review: the misconfigurations from ConfigurationChecks
 * plus the hardening checks from SecurityChecks, most severe first.
 */
class SecurityReportAbility extends AbstractAbility
{
    /**
     * Points removed from 100 per finding.
     *
     * @var array<string, int>
     */
    public static $penalties = array(
        'critical' => 25,
        'warning'  => 10,
        'info'     => 3,
    );

    /**
     * Minimum score per grade, best first.
     *
     * @var array<string, int>
     */
    public static $grades = array(
        'A' => 90,
        'B' => 75,
        'C' => 60,
        'D' => 40,
        'F' => 0,
    );

    /**
     * @var TwoFactorBridge
     */
    private $twoFactorBridge;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param TwoFactorBridge        $twoFactorBridge
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, TwoFactorBridge $twoFactorBridge)
    {
        parent::__construct($jwtSettings);
        $this->twoFactorBridge = $twoFactorBridge;
    }

    protected function getSlug()
    {
        return 'security-report';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Simple JWT Login security report', 'simple-jwt-login'),
            'description'   => __(
                'Reviews the plugin configuration for security risks and returns a score (0-100), a grade (A-F) and the findings, most severe first. Each finding names the settings paths to change. Covers open registration/deletion, administrator roles, weak or missing signing keys, long JWT lifetimes, unrestricted autologin, open redirects, JWTs in URLs, CORS and logging. Secrets are never returned. Use it when asked whether the setup is secure or how to harden it.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'min_severity' => [
                    'type'        => 'string',
                    'enum'        => ['info', 'warning', 'critical'],
                    'default'     => 'info',
                    'description' => __('Only list findings at least this severe. The score always counts every finding.', 'simple-jwt-login'),
                ],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'score'    => ['type' => 'integer'],
                    'grade'    => ['type' => 'string'],
                    'counts'   => ['type' => 'object'],
                    'findings' => ['type' => 'array'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $findings = array_merge(
            (new ConfigurationChecks($this->jwtSettings, $this->twoFactorBridge))->run(),
            (new SecurityChecks($this->jwtSettings))->run()
        );

        return self::summarize($findings, (string) $this->inputValue($input, 'min_severity', 'info'));
    }

    /**
     * @param array[] $findings    Each with a "severity" key.
     * @param string  $minSeverity Findings less severe than this are left out of the list (not the score).
     * @return array
     */
    public static function summarize(array $findings, $minSeverity = 'info')
    {
        $order  = array_keys(self::$penalties);
        $counts = array_fill_keys($order, 0);
        $score  = 100;
        foreach ($findings as $finding) {
            $severity = $finding['severity'];
            $counts[$severity]++;
            $score -= self::$penalties[$severity];
        }
        usort($findings, function ($left, $right) use ($order) {
            return array_search($left['severity'], $order, true) - array_search($right['severity'], $order, true);
        });
        $score    = max(0, $score);
        $maxIndex = array_search($minSeverity, $order, true);
        $maxIndex = $maxIndex === false ? count($order) - 1 : $maxIndex;
        $listed   = array_filter($findings, function ($finding) use ($order, $maxIndex) {
            return array_search($finding['severity'], $order, true) <= $maxIndex;
        });

        return [
            'score'    => $score,
            'grade'    => self::grade($score),
            'counts'   => (object) $counts,
            'findings' => array_values($listed),
        ];
    }

    /**
     * @param int $score
     * @return string
     */
    private static function grade($score)
    {
        foreach (self::$grades as $grade => $minimum) {
            if ($score >= $minimum) {
                return $grade;
            }
        }

        return 'F';
    }
}
