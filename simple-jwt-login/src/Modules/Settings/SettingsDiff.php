<?php

namespace SimpleJWTLogin\Modules\Settings;

/**
 * Computes a human-readable diff between two settings arrays.
 *
 * Extracted from SimpleJWTLoginSettings so the facade stays focused on
 * settings access while the (pure, side-effect free) diff logic can be
 * tested and evolved on its own.
 */
class SettingsDiff
{
    /**
     * Keys containing any of these substrings have their values redacted.
     *
     * @var array
     */
    private static $sensitivePatterns = array('secret', 'password', '_key');

    /**
     * Compute a flat diff between two settings arrays.
     *
     * @param array $old
     * @param array $new
     * @return array
     */
    public function build($old, $new)
    {
        $flatOld = $this->flatten($old);
        $flatNew = $this->flatten($new);

        $changed = [];
        $added   = [];
        $removed = [];

        foreach ($flatNew as $key => $value) {
            if (!array_key_exists($key, $flatOld)) {
                $added[] = $key;
                continue;
            }
            if ($flatOld[$key] !== $value) {
                $changed[$key] = [
                    'from' => $this->redactIfSensitive($key, $flatOld[$key]),
                    'to'   => $this->redactIfSensitive($key, $value),
                ];
            }
        }

        foreach (array_keys($flatOld) as $key) {
            if (!array_key_exists($key, $flatNew)) {
                $removed[] = $key;
            }
        }

        return array_filter([
            'changed' => $changed,
            'added'   => $added,
            'removed' => $removed,
        ]);
    }

    /**
     * Flatten a nested settings array into dot-notation keys.
     * Indexed (list) arrays are serialised as JSON strings rather than recursed into.
     *
     * @param array  $settings
     * @param string $prefix
     * @return array<string, string>
     */
    private function flatten($settings, $prefix = '')
    {
        $result = [];
        if (!is_array($settings)) {
            return $result;
        }
        foreach ($settings as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : (string) $key;
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
                continue;
            }
            $result[$fullKey] = is_array($value) ? (string) json_encode($value) : (string) $value;
        }
        return $result;
    }

    /**
     * Replace the value with '[REDACTED]' for keys that may hold sensitive data.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    private function redactIfSensitive($key, $value)
    {
        $lowerKey = strtolower($key);
        foreach (self::$sensitivePatterns as $pattern) {
            if (strpos($lowerKey, $pattern) !== false) {
                return '[REDACTED]';
            }
        }
        return $value;
    }
}
