<?php

namespace SimpleJWTLogin\Modules\Abilities;

/**
 * Returns a copy of the plugin settings with every secret masked, so it can
 * be handed to an AI agent through the Abilities API. Base64-stored values
 * are decoded, so the agent reads the same plain text it writes.
 *
 * Secrets are left in clear text only when the policy grants the "view
 * secrets" permission. maskUrlQuery() and redactAuditMessage() always mask:
 * they feed logs, which must never hold secrets.
 */
class SettingsRedactor
{
    const MASK = '********';

    /**
     * @var AbilitySettingsPolicy
     */
    private $policy;

    /**
     * @param AbilitySettingsPolicy $policy
     */
    public function __construct(AbilitySettingsPolicy $policy)
    {
        $this->policy = $policy;
    }

    /**
     * @param mixed $settings
     * @return array
     */
    public function redact($settings)
    {
        $redacted = $this->redactNode(is_array($settings) ? $settings : [], '');

        if (isset($redacted['webhooks']['items']) && is_array($redacted['webhooks']['items'])) {
            $redacted['webhooks']['items'] = array_map(
                array($this, 'redactWebhook'),
                $redacted['webhooks']['items']
            );
        }

        return $redacted;
    }

    /**
     * Mask the header values and URL query values of a single webhook
     * (they often hold bearer tokens / API keys).
     *
     * @param mixed $webhook
     * @return mixed
     */
    public function redactWebhook($webhook)
    {
        if ($this->policy->canViewSecrets()) {
            return $webhook;
        }
        if (isset($webhook['url']) && is_string($webhook['url'])) {
            $webhook['url'] = $this->maskUrlQuery($webhook['url']);
        }
        if (!isset($webhook['headers']) || !is_array($webhook['headers'])) {
            return $webhook;
        }

        foreach ($webhook['headers'] as $index => $header) {
            if (is_array($header) && array_key_exists('value', $header)) {
                $webhook['headers'][$index]['value'] = $this->mask($header['value']);
            }
        }

        return $webhook;
    }

    /**
     * Mask every query-string value of a URL ("?token=abc&x" => "?token=********&x").
     *
     * @param string $url
     * @return string
     */
    public function maskUrlQuery($url)
    {
        $url        = (string) $url;
        $queryStart = strpos($url, '?');
        if ($queryStart === false) {
            return $url;
        }
        $masked = [];
        foreach (explode('&', substr($url, $queryStart + 1)) as $pair) {
            $parts    = explode('=', $pair, 2);
            $masked[] = count($parts) === 2 ? $parts[0] . '=' . self::MASK : $parts[0];
        }

        return substr($url, 0, $queryStart + 1) . implode('&', $masked);
    }

    /**
     * Redact the settings diff inside an audit log message. Entries written
     * before the diff was redacted hold secrets in clear text (lists such as
     * auth codes are serialised as JSON). Non-JSON messages are returned as is.
     *
     * @param string|null $message
     * @return string|null
     */
    public function redactAuditMessage($message)
    {
        $decoded = is_string($message) ? json_decode($message, true) : null;
        if (!is_array($decoded)) {
            return $message;
        }
        if (isset($decoded['changed'])) {
            $decoded = $this->redactDiff($decoded);
        }
        if (isset($decoded['diff']) && is_array($decoded['diff'])) {
            $decoded['diff'] = $this->redactDiff($decoded['diff']);
        }

        return (string) json_encode($decoded);
    }

    /**
     * @param array $diff SettingsDiff::build() shape.
     * @return array
     */
    private function redactDiff(array $diff)
    {
        if (!isset($diff['changed']) || !is_array($diff['changed'])) {
            return $diff;
        }
        foreach ($diff['changed'] as $path => $change) {
            if (!is_array($change) || $this->policy->isBase64((string) $path)) {
                continue;
            }
            foreach (array('from', 'to') as $side) {
                if (isset($change[$side]) && is_scalar($change[$side])) {
                    $diff['changed'][$path][$side] = $this->redactDiffValue((string) $path, (string) $change[$side]);
                }
            }
        }

        return $diff;
    }

    /**
     * Redact one flattened diff value by placing it back at its settings path.
     *
     * @param string $path
     * @param string $value Scalar value, or a JSON-serialised list.
     * @return string
     */
    private function redactDiffValue($path, $value)
    {
        $list   = json_decode($value, true);
        $isList = is_array($list);
        $tree   = $isList ? $list : $value;
        foreach (array_reverse(explode('.', $path)) as $segment) {
            $tree = [$segment => $tree];
        }
        // A policy without permissions: log contents are masked even when the AI may view secrets.
        $redacted = SettingsPath::get((new self(new AbilitySettingsPolicy()))->redact($tree), $path);

        return $isList ? (string) json_encode($redacted) : (string) $redacted;
    }

    /**
     * @param array  $node
     * @param string $prefix
     * @return array
     */
    private function redactNode(array $node, $prefix)
    {
        foreach ($node as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $node[$key] = $this->redactNode($value, $path);
                continue;
            }
            if ($this->policy->isSecret($path) && !$this->policy->canViewSecrets()) {
                $node[$key] = $this->mask($value);
                continue;
            }
            if ($this->policy->isBase64($path) && is_string($value)) {
                $node[$key] = (string) base64_decode($value);
            }
        }

        return $node;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function mask($value)
    {
        return ($value === null || $value === '') ? '' : self::MASK;
    }
}
