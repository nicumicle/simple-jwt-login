<?php

namespace SimpleJWTLogin\Modules\Abilities;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

/**
 * Applies a partial update coming from an AI agent to the stored settings array.
 *
 * Only keys that already exist in the stored settings can be changed, each new
 * value is cast to the type of the stored one, and every path is checked
 * against AbilitySettingsPolicy. The input array is never mutated; a new
 * settings array is returned.
 *
 * Lists of objects holding secrets (auth codes, JWT rules) are never replaced. They take a list of operations instead:
 * {index, match, set} edits one item, {index, match, remove: true} drops it.
 * "match" must equal the stored non-secret fields, so a stale index fails
 * instead of changing the wrong item, and secret fields are carried over.
 * Items cannot be added: a new auth code or JWT rule always needs a secret.
 */
class SettingsPatcher
{
    /**
     * @var AbilitySettingsPolicy
     */
    private $policy;

    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    /**
     * @param AbilitySettingsPolicy  $policy
     * @param WordPressDataInterface $wordPressData
     */
    public function __construct(AbilitySettingsPolicy $policy, WordPressDataInterface $wordPressData)
    {
        $this->policy        = $policy;
        $this->wordPressData = $wordPressData;
    }

    /**
     * @param array  $settings Full stored settings.
     * @param string $section  Top-level section key (e.g. "login").
     * @param mixed  $values   Nested map of the keys to change.
     * @return array The new full settings array.
     * @throws Exception
     */
    public function apply(array $settings, $section, $values)
    {
        if (!is_string($section) || !isset($settings[$section]) || !is_array($settings[$section])) {
            throw new Exception(
                esc_html__('Unknown settings section.', 'simple-jwt-login'),
                absint(ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION)
            );
        }
        if (!is_array($values) || empty($values)) {
            throw new Exception(
                esc_html__('Provide at least one setting to change.', 'simple-jwt-login'),
                absint(ErrorCodes::ERR_ABILITY_INVALID_VALUE)
            );
        }
        $this->assertWritable($section);

        $settings[$section] = $this->mergeNode($settings[$section], $values, $section);
        $this->assertParallelLists($settings);

        return $settings;
    }

    /**
     * @param array  $current
     * @param array  $values
     * @param string $path
     * @return array
     * @throws Exception
     */
    private function mergeNode(array $current, array $values, $path)
    {
        foreach ($values as $key => $value) {
            $childPath = $path . '.' . $key;
            $this->assertWritable($childPath);
            if (!array_key_exists($key, $current)) {
                throw new Exception(
                    // translators: %s = settings path, e.g. login.enabled
                    esc_html(sprintf(__('Unknown setting: %s', 'simple-jwt-login'), $childPath)),
                    absint(ErrorCodes::ERR_ABILITY_UNKNOWN_SETTING)
                );
            }
            $current[$key] = $this->castValue($current[$key], $value, $childPath);
        }

        return $current;
    }

    /**
     * @param mixed  $existing
     * @param mixed  $value
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    private function castValue($existing, $value, $path)
    {
        if (is_array($existing)) {
            return $this->castArray($existing, $value, $path);
        }
        if (is_bool($existing)) {
            return $this->castBool($value, $path);
        }
        if (is_int($existing)) {
            if (is_bool($value)) {
                return (int) $value;
            }
            if (!is_numeric($value)) {
                $this->throwInvalidValue($path);
            }
            return (int) $value;
        }
        if (!is_scalar($value) && $value !== null) {
            $this->throwInvalidValue($path);
        }
        if ($this->policy->isBase64($path)) {
            return base64_encode($this->wordPressData->ksesPost((string) $value));
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $sanitized = $this->wordPressData->sanitizeTextField((string) $value);
        if ($this->policy->isRoleValuePath($path)) {
            $this->assertNotPrivilegedRole($sanitized, $path);
        }

        return $sanitized;
    }

    /**
     * @param string $role
     * @param string $path
     * @throws Exception
     */
    private function assertNotPrivilegedRole($role, $path)
    {
        $capabilities = (array) $this->wordPressData->getRoleCapabilities($role);
        if (!$this->policy->isPrivilegedRole($role, $capabilities)) {
            return;
        }

        throw new Exception(
            esc_html(sprintf(
                // translators: 1: role slug, 2: settings path
                __('AI agents cannot set the role "%1$s" on "%2$s": it has administrator capabilities. Set it from the plugin settings page.', 'simple-jwt-login'),
                $role,
                $path
            )),
            absint(ErrorCodes::ERR_ABILITY_PERMISSION_DENIED)
        );
    }

    /**
     * @param array  $existing
     * @param mixed  $value
     * @param string $path
     * @return array
     * @throws Exception
     */
    private function castArray(array $existing, $value, $path)
    {
        if (!is_array($value)) {
            $this->throwInvalidValue($path);
        }
        if ($this->policy->isSecretList($path)) {
            // Checked before the empty-list shortcut: items can never be added here.
            return $this->patchObjectList($existing, $value, $path);
        }
        if (empty($existing)) {
            // Nothing stored yet (e.g. the first custom claim): accept the new structure.
            return $this->sanitizeTree($value, $path);
        }
        if (!$this->isList($existing)) {
            return $this->mergeNode($existing, $value, $path);
        }
        if (!$this->isList($value)) {
            $this->throwInvalidValue($path);
        }
        if (is_array(reset($existing))) {
            return $this->itemHasSecrets(reset($existing), $path)
                ? $this->patchObjectList($existing, $value, $path)
                : $this->sanitizeTree(array_values($value), $path);
        }

        $result = [];
        foreach (array_values($value) as $item) {
            if (!is_scalar($item)) {
                $this->throwInvalidValue($path);
            }
            $result[] = $this->wordPressData->sanitizeTextField((string) $item);
        }

        return $result;
    }

    /**
     * Sanitize a new value that has no stored counterpart to cast against.
     *
     * @param mixed  $value
     * @param string $path
     * @return mixed
     * @throws Exception
     */
    private function sanitizeTree($value, $path)
    {
        if (!is_array($value)) {
            if (!is_scalar($value) && $value !== null) {
                $this->throwInvalidValue($path);
            }
            return is_bool($value) || is_int($value) ? $value : $this->wordPressData->sanitizeTextField((string) $value);
        }
        $result = [];
        foreach ($value as $key => $item) {
            $childPath = $path . '.' . $key;
            $this->assertWritable($childPath);
            $result[$key] = $this->sanitizeTree($item, $childPath);
        }

        return $result;
    }

    /**
     * @param array  $item
     * @param string $path
     * @return bool
     */
    private function itemHasSecrets(array $item, $path)
    {
        foreach (array_keys($item) as $key) {
            if ($this->policy->isSecret($path . '.0.' . $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $settings
     * @throws Exception
     */
    private function assertParallelLists(array $settings)
    {
        foreach (AbilitySettingsPolicy::$parallelLists as $parentPath => $keys) {
            $parent = SettingsPath::get($settings, $parentPath);
            if (!is_array($parent) || empty($parent)) {
                continue;
            }
            $lengths = [];
            foreach ($keys as $key) {
                $lengths[$key] = isset($parent[$key]) && is_array($parent[$key]) ? count($parent[$key]) : 0;
            }
            if (count(array_unique($lengths)) > 1) {
                throw new Exception(
                    esc_html(sprintf(
                        // translators: 1: settings path, 2: list of keys
                        __('The lists under %1$s (%2$s) hold one entry per index and must have the same length.', 'simple-jwt-login'),
                        $parentPath,
                        implode(', ', $keys)
                    )),
                    absint(ErrorCodes::ERR_ABILITY_INVALID_VALUE)
                );
            }
        }
    }

    /**
     * @param array  $items
     * @param array  $operations
     * @param string $path
     * @return array
     * @throws Exception
     */
    private function patchObjectList(array $items, array $operations, $path)
    {
        $seen    = [];
        $removed = [];
        foreach ($operations as $operation) {
            $index = $this->resolveListItem($items, $operation, $path, $seen);
            $seen[$index] = true;
            if (!empty($operation['remove'])) {
                $removed[] = $index;
                continue;
            }
            $items[$index] = $this->normalizeListItem(
                $path,
                $this->mergeNode($items[$index], $operation['set'], $path . '.' . $index)
            );
        }
        foreach ($removed as $index) {
            unset($items[$index]);
        }

        return array_values($items);
    }

    /**
     * Validate one list operation and return the index of the item it targets.
     *
     * @param array  $items
     * @param mixed  $operation
     * @param string $path
     * @param array  $seen Indexes already targeted by earlier operations.
     * @return int
     * @throws Exception
     */
    private function resolveListItem(array $items, $operation, $path, array $seen)
    {
        if (!is_array($operation)
            || !isset($operation['index'], $operation['match'])
            || !is_numeric($operation['index'])
            || !is_array($operation['match'])
            || empty($operation['match'])
            || isset($seen[(int) $operation['index']])
        ) {
            $this->throwInvalidListChange($path);
        }
        $hasSet    = isset($operation['set']) && is_array($operation['set']) && !empty($operation['set']);
        $hasRemove = isset($operation['remove']) && $operation['remove'] === true;
        if ($hasSet === $hasRemove) {
            $this->throwInvalidListChange($path);
        }

        $index = (int) $operation['index'];
        foreach ($operation['match'] as $key => $expected) {
            $this->assertItemMatches($items, $index, $key, $expected, $path);
        }

        return $index;
    }

    /**
     * @param array  $items
     * @param int    $index
     * @param string $key
     * @param mixed  $expected
     * @param string $path
     * @throws Exception
     */
    private function assertItemMatches(array $items, $index, $key, $expected, $path)
    {
        $itemPath = $path . '.' . $index;
        if ($this->policy->isSecret($itemPath . '.' . $key)) {
            // Matching on a secret would let an agent guess it one try at a time.
            $this->throwNotWritable($itemPath . '.' . $key);
        }
        if (!is_scalar($expected)) {
            $this->throwInvalidListChange($path);
        }
        if (!isset($items[$index]) || !is_array($items[$index])
            || !array_key_exists($key, $items[$index])
            || !is_scalar($items[$index][$key])
            || $this->scalarString($items[$index][$key]) !== $this->scalarString($expected)
        ) {
            throw new Exception(
                esc_html(sprintf(
                    // translators: %s = settings path of a list item, e.g. auth_codes.codes.0
                    __('The list item %s does not match. Read the settings again and retry.', 'simple-jwt-login'),
                    $itemPath
                )),
                absint(ErrorCodes::ERR_ABILITY_LIST_ITEM_MISMATCH)
            );
        }
    }

    /**
     * Repeat the normalization the admin form applies to JWT rules: an "iss"
     * rule keeps its issuer in both condition_value and iss.
     *
     * @param string $path
     * @param array  $item
     * @return array
     */
    private function normalizeListItem($path, array $item)
    {
        if ($path !== 'jwt_rules.rules' || !isset($item['condition_type'])) {
            return $item;
        }
        if ($item['condition_type'] !== 'iss') {
            unset($item['iss']);
            return $item;
        }
        $item['condition_key'] = 'iss';
        $item['iss']           = isset($item['condition_value']) ? $item['condition_value'] : '';

        return $item;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function scalarString($value)
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @param mixed  $value
     * @param string $path
     * @return bool
     * @throws Exception
     */
    private function castBool($value, $path)
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) (is_scalar($value) ? $value : '')));
        if (in_array($normalized, array('1', 'true', 'yes', 'on'), true)) {
            return true;
        }
        if (in_array($normalized, array('0', 'false', 'no', 'off', ''), true) && is_scalar($value)) {
            return false;
        }

        $this->throwInvalidValue($path);

        return false;
    }

    /**
     * Empty arrays are treated as lists.
     *
     * @param array $value
     * @return bool
     */
    private function isList(array $value)
    {
        return empty($value) || array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function throwInvalidValue($path)
    {
        throw new Exception(
            // translators: %s = settings path, e.g. login.enabled
            esc_html(sprintf(__('Invalid value for setting: %s', 'simple-jwt-login'), $path)),
            absint(ErrorCodes::ERR_ABILITY_INVALID_VALUE)
        );
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function throwInvalidListChange($path)
    {
        throw new Exception(
            esc_html(sprintf(
                // translators: %s = settings path of a list, e.g. auth_codes.codes
                __('Invalid change for the list %s. Send a list of {index, match, set} or {index, match, remove: true} operations, each targeting a different item. New items need secrets, so add them from the plugin settings page.', 'simple-jwt-login'),
                $path
            )),
            absint(ErrorCodes::ERR_ABILITY_INVALID_VALUE)
        );
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function assertWritable($path)
    {
        $permission = $this->policy->missingPermission($path);
        if ($permission !== '') {
            throw new Exception(
                esc_html(sprintf(
                    // translators: 1: settings path, 2: name of the AI permission option
                    __('The setting "%1$s" can only be changed by AI agents when an administrator enables "%2$s" in Integrations > AI Abilities (MCP).', 'simple-jwt-login'),
                    $path,
                    AiAbilitiesSettings::getPermissionLabel($permission)
                )),
                absint(ErrorCodes::ERR_ABILITY_PERMISSION_DENIED)
            );
        }
        if (!$this->policy->isWritable($path)) {
            $this->throwNotWritable($path);
        }
    }

    /**
     * @param string $path
     * @throws Exception
     */
    private function throwNotWritable($path)
    {
        throw new Exception(
            esc_html(sprintf(
                // translators: %s = settings path, e.g. general.decryption_key
                __('The setting "%s" cannot be changed through AI abilities. Change it from the plugin settings page.', 'simple-jwt-login'),
                $path
            )),
            absint(ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE)
        );
    }
}
