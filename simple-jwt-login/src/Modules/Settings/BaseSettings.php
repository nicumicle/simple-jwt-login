<?php

namespace SimpleJWTLogin\Modules\Settings;

use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

abstract class BaseSettings
{
    const SETTINGS_TYPE_INT = 0;
    const SETTINGS_TYPE_BOL = 1;
    const SETTINGS_TYPE_STRING = 2;
    const SETTINGS_TYPE_ARRAY = 3;
    const SETTINGS_TYPE_TEXTAREA = 4;

    /**
     * @var SettingsErrors $settingsErrors
     */
    protected $settingsErrors;

    public function __construct()
    {
        $this->settingsErrors = new SettingsErrors();
    }
    /**
     * @var array|null
     */
    protected $settings;
    /**
     * @var array
     */
    protected $fullSettings = [];
    /**
     * @var array|null
     */
    protected $post;
    /**
     * @var WordPressDataInterface
     */
    protected $wordPressData;

    /**
     * Returns the DB section key for this settings class (e.g. 'login', 'register').
     *
     * @return string
     */
    abstract protected function getSectionKey();

    /**
     * @return array|null
     */
    public function getSettings()
    {
        return [$this->getSectionKey() => $this->settings];
    }

    /**
     * @param array|null $post
     * @return $this
     */
    public function withPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @param array|null $settings
     * @return $this
     */
    public function withSettings($settings)
    {
        $this->fullSettings = is_array($settings) ? $settings : [];
        $key = $this->getSectionKey();
        $this->settings = isset($settings[$key]) ? $settings[$key] : [];

        return $this;
    }

    /**
     * @param WordPressDataInterface $wordPressData
     * @return $this
     */
    public function withWordPressData($wordPressData)
    {
        $this->wordPressData = $wordPressData;

        return $this;
    }

    /**
     * Declarative field map for this settings class.
     *
     * Each row is the positional argument list for assignSettingsPropertyFromPost():
     * array($propertyGroup, $propertyName, $postKeyGroup, $postKey, $type, $defaultValue, $base64Encode).
     * Subclasses override this to declare their fields instead of hand-writing
     * initSettingsFromPost(). Defaults to an empty list so nothing breaks.
     *
     * @return array<int, array>
     */
    protected function getFieldDefinitions()
    {
        return [];
    }

    /**
     * Populate $this->settings from $this->post using the declarative field map.
     *
     * Subclasses with extra, non-declarative logic should override this, call
     * parent::initSettingsFromPost() first, then run their bespoke handling.
     *
     * @return void
     */
    public function initSettingsFromPost()
    {
        foreach ($this->getFieldDefinitions() as $field) {
            call_user_func_array([$this, 'assignSettingsPropertyFromPost'], $field);
        }
    }

    /**
     * @param null|string $propertyGroup
     * @param string $propertyName
     * @param null|string $postKeyGroup
     * @param string $postKey
     * @param int|null $type
     * @param string|int|boolean|null $defaultValue
     * @param null|bool $base64Encode
     */
    protected function assignSettingsPropertyFromPost(
        $propertyGroup,
        $propertyName,
        $postKeyGroup,
        $postKey,
        $type = null,
        $defaultValue = null,
        $base64Encode = null
    ) {
        $postKeyExists = $postKeyGroup !== null
            ? isset($this->post[$postKeyGroup][$postKey])
            : isset($this->post[$postKey]);

        if (!$postKeyExists) {
            if ($defaultValue !== null) {
                $defaultValue = $base64Encode
                    ? base64_encode($defaultValue)
                    : $defaultValue;
                $this->assignProperty($defaultValue, $propertyName, $propertyGroup);
                return;
            }
            if ($type === self::SETTINGS_TYPE_ARRAY) {
                $this->assignProperty([], $propertyName, $propertyGroup);
                return;
            }
            if ($type === self::SETTINGS_TYPE_BOL) {
                $this->assignProperty(false, $propertyName, $propertyGroup);
            }
            return;
        }

        $postValue = $postKeyGroup !== null
            ? $this->post[$postKeyGroup][$postKey]
            : $this->post[$postKey];
        switch ($type) {
            case self::SETTINGS_TYPE_INT:
                $value = (int) $postValue;
                break;
            case self::SETTINGS_TYPE_BOL:
                $value = (bool)$postValue;
                break;
            case self::SETTINGS_TYPE_STRING:
                $value = $base64Encode
                    ? base64_encode($postValue)
                    : $this->wordPressData->sanitizeTextField($postValue);
                break;
            case self::SETTINGS_TYPE_ARRAY:
                $value = $this->sanitizeArray($postValue);
                break;
            default:
                $value = $this->wordPressData->sanitizeTextField($postValue);
                break;
        }

        $this->assignProperty($value, $propertyName, $propertyGroup);
    }

    private function assignProperty($value, $propertyName, $propertyGroup = null)
    {
        if ($propertyGroup !== null) {
            $this->settings[$propertyGroup][$propertyName] = $value;
            return;
        }
        $this->settings[$propertyName] = $value;
    }

    /**
     * Recursive sanitation for an array
     *
     * @param array $array
     *
     * @return array
     */
    private function sanitizeArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $sanitizedKey = $this->wordPressData->sanitizeTextField($key);

            if (is_array($value)) {
                $result[$sanitizedKey] = $this->sanitizeArray($value);
                continue;
            }
            if (is_string($value) || is_int($value) || is_numeric($value)) {
                $result[$sanitizedKey] = $this->wordPressData->sanitizeTextField($value);
                continue;
            }
            if (is_null($value)) {
                $result[$sanitizedKey] = null;
                continue;
            }
            $result[$sanitizedKey] = $value;
        }

        return $result;
    }
}
