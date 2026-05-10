<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class JwtRulesSettings extends BaseSettings implements SettingsInterface
{
    public function initSettingsFromPost()
    {
        if (!isset($this->post['jwt_rules'])) {
            $this->settings['jwt_rules'] = [];
            return;
        }

        $raw = $this->post['jwt_rules'];
        if (is_string($raw)) {
            $decoded = json_decode(stripslashes($raw), true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            $this->settings['jwt_rules'] = [];
            return;
        }

        $rules = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $conditionType = $this->wordPressData->sanitizeTextField(
                isset($entry['condition_type']) ? (string)$entry['condition_type'] : ''
            );
            $conditionOperator = $this->wordPressData->sanitizeTextField(
                isset($entry['condition_operator']) ? (string)$entry['condition_operator'] : ''
            );
            $conditionKey = $this->wordPressData->sanitizeTextField(
                isset($entry['condition_key']) ? (string)$entry['condition_key'] : ''
            );
            $conditionValue = $this->wordPressData->sanitizeTextField(
                isset($entry['condition_value']) ? (string)$entry['condition_value'] : ''
            );

            if ($conditionType === '') {
                $conditionType = 'iss';
            }
            if ($conditionOperator === '') {
                $conditionOperator = 'equals';
            }
            if ($conditionType === 'iss') {
                if ($conditionValue === '') {
                    $conditionValue = $this->wordPressData->sanitizeTextField(
                        isset($entry['iss']) ? (string)$entry['iss'] : ''
                    );
                }
                $conditionKey = 'iss';
            }

            if ($conditionType !== 'iss' && $conditionKey === '') {
                continue;
            }
            if ($conditionValue === '') {
                continue;
            }

            $algorithm = $this->wordPressData->sanitizeTextField(
                isset($entry['algorithm']) ? (string)$entry['algorithm'] : 'HS256'
            );

            $loginBy = isset($entry['login_by']) ? (int)$entry['login_by'] : 0;
            $loginByParameter = $this->wordPressData->sanitizeTextField(
                isset($entry['login_by_parameter']) ? (string)$entry['login_by_parameter'] : ''
            );

            $rule = [
                'condition_type'      => $conditionType,
                'condition_key'       => $conditionKey,
                'condition_operator'  => $conditionOperator,
                'condition_value'     => $conditionValue,
                'algorithm'           => $algorithm,
                'login_by'            => $loginBy,
                'login_by_parameter'  => $loginByParameter,
            ];

            if ($conditionType === 'iss') {
                $rule['iss'] = $conditionValue;
            }

            if (strpos($algorithm, 'RS') !== false) {
                $rule['decryption_key_public']  = base64_encode(
                    isset($entry['decryption_key_public']) ? (string)$entry['decryption_key_public'] : ''
                );
                $rule['decryption_key_private'] = base64_encode(
                    isset($entry['decryption_key_private']) ? (string)$entry['decryption_key_private'] : ''
                );
                $rules[] = $rule;
                continue;
            }

            $rule['decryption_key']        = $this->wordPressData->sanitizeTextField(
                isset($entry['decryption_key']) ? (string)$entry['decryption_key'] : ''
            );
            $rule['decryption_key_base64'] = !empty($entry['decryption_key_base64']);

            $rules[] = $rule;
        }

        $this->settings['jwt_rules'] = $rules;
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        $rules = $this->getRules();
        $seen  = [];

        foreach ($rules as $i => $rule) {
            $conditionType = isset($rule['condition_type'])
                ? trim((string)$rule['condition_type'])
                : 'iss';
            $conditionKey = isset($rule['condition_key'])
                ? trim((string)$rule['condition_key'])
                : ($conditionType === 'iss' ? 'iss' : '');
            $conditionOperator = isset($rule['condition_operator'])
                ? trim((string)$rule['condition_operator'])
                : 'equals';
            $conditionValue = isset($rule['condition_value'])
                ? trim((string)$rule['condition_value'])
                : (
                    $conditionType === 'iss' && isset($rule['iss'])
                        ? trim((string)$rule['iss'])
                        : ''
                );

            if ($conditionType === 'iss' && $conditionValue === '') {
                throw new Exception(
                    sprintf(
                        __('JWT Rule #%d: the "iss" value cannot be empty.', 'simple-jwt-login'),
                        $i + 1
                    )
                );
            }

            if ($conditionType !== 'iss' && $conditionKey === '') {
                throw new Exception(
                    sprintf(
                        __('JWT Rule #%d: the condition key cannot be empty.', 'simple-jwt-login'),
                        $i + 1
                    )
                );
            }

            if ($conditionValue === '') {
                throw new Exception(
                    sprintf(
                        __('JWT Rule #%d: the condition value cannot be empty.', 'simple-jwt-login'),
                        $i + 1
                    )
                );
            }

            $signature = sprintf('%s|%s|%s|%s', $conditionType, $conditionKey, $conditionOperator, $conditionValue);
            if (in_array($signature, $seen, true)) {
                throw new Exception(
                    sprintf(
                        __('JWT Rule #%d: duplicate condition "%s".', 'simple-jwt-login'),
                        $i + 1,
                        $signature
                    )
                );
            }
            $seen[] = $signature;

            $algorithm = isset($rule['algorithm']) ? $rule['algorithm'] : '';
            if (strpos($algorithm, 'RS') !== false) {
                $pub  = isset($rule['decryption_key_public'])
                    ? trim((string)base64_decode($rule['decryption_key_public']))
                    : '';
                $priv = isset($rule['decryption_key_private'])
                    ? trim((string)base64_decode($rule['decryption_key_private']))
                    : '';
                if ($pub === '' || $priv === '') {
                    throw new Exception(
                        sprintf(
                            __(
                                'JWT Rule #%d ("%s"): public and private keys are required for RS* algorithms.',
                                'simple-jwt-login'
                            ),
                            $i + 1,
                            $conditionValue
                        )
                    );
                }
                continue;
            }

            $key = isset($rule['decryption_key']) ? trim($rule['decryption_key']) : '';
            if ($key === '') {
                throw new Exception(
                    sprintf(
                        __('JWT Rule #%d ("%s"): decryption key is required.', 'simple-jwt-login'),
                        $i + 1,
                        $conditionValue
                    )
                );
            }
        }
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return isset($this->settings['jwt_rules']) && is_array($this->settings['jwt_rules'])
            ? $this->settings['jwt_rules']
            : [];
    }

    /**
     * @param string $iss
     * @return array|null
     */
    public function findByIss($iss)
    {
        foreach ($this->getRules() as $rule) {
            if (isset($rule['condition_type']) && $rule['condition_type'] === 'iss') {
                $conditionValue = isset($rule['condition_value'])
                    ? $rule['condition_value']
                    : (isset($rule['iss']) ? $rule['iss'] : '');
                if ($conditionValue === $iss) {
                    return $rule;
                }
            }

            if (isset($rule['iss']) && $rule['iss'] === $iss) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * @param array $jwtParts
     * @return array|null
     */
    public function findMatchingRuleConfig(array $jwtParts)
    {
        foreach ($this->getRules() as $rule) {
            if ($this->matchesRuleCondition($rule, $jwtParts)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @param array $rule
     * @param array $jwtParts
     * @return bool
     */
    private function matchesRuleCondition(array $rule, array $jwtParts)
    {
        $conditionType = isset($rule['condition_type'])
            ? trim((string)$rule['condition_type'])
            : 'iss';
        $conditionKey = isset($rule['condition_key'])
            ? trim((string)$rule['condition_key'])
            : ($conditionType === 'iss' ? 'iss' : '');
        $conditionOperator = isset($rule['condition_operator'])
            ? trim((string)$rule['condition_operator'])
            : 'equals';
        $conditionValue = isset($rule['condition_value'])
            ? trim((string)$rule['condition_value'])
            : (
                $conditionType === 'iss' && isset($rule['iss'])
                    ? trim((string)$rule['iss'])
                    : ''
            );

        if ($conditionValue === '') {
            return false;
        }

        if (!in_array($conditionType, ['iss', 'payload', 'header'], true)) {
            return false;
        }

        $valueToCheck = '';
        if ($conditionType === 'iss') {
            $valueToCheck = isset($jwtParts['payload']['iss']) ? (string)$jwtParts['payload']['iss'] : '';
        }
        if ($conditionType === 'payload') {
            if ($conditionKey === '') {
                return false;
            }
            $valueToCheck = isset($jwtParts['payload'][$conditionKey]) ? (string)$jwtParts['payload'][$conditionKey] : '';
        }
        if ($conditionType === 'header') {
            if ($conditionKey === '') {
                return false;
            }
            $valueToCheck = isset($jwtParts['header'][$conditionKey]) ? (string)$jwtParts['header'][$conditionKey] : '';
        }

        if ($conditionOperator === 'contains') {
            return $conditionValue !== '' && strpos($valueToCheck, $conditionValue) !== false;
        }

        return $valueToCheck === $conditionValue;
    }
}
