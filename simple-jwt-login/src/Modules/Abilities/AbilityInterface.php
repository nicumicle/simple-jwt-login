<?php

namespace SimpleJWTLogin\Modules\Abilities;

use Exception;

/**
 * One Simple JWT Login action exposed through the WordPress Abilities API.
 */
interface AbilityInterface
{
    /**
     * Namespaced ability name, e.g. "simple-jwt-login/get-settings".
     *
     * @return string
     */
    public function getName();

    /**
     * Ability arguments without the callbacks and category:
     * label, description, input_schema, output_schema, annotations.
     *
     * @return array
     */
    public function getDefinition();

    /**
     * True when the ability never changes anything.
     *
     * @return bool
     */
    public function isReadOnly();

    /**
     * Opt-in AI permission (AiAbilitiesSettings::PERMISSION_*) the
     * administrator must grant before this ability is registered or run,
     * or '' when the access mode alone is enough.
     *
     * @return string
     */
    public function getRequiredPermission();

    /**
     * WordPress capability the current user needs to run this ability,
     * checked on every call.
     *
     * @return string
     */
    public function getRequiredCapability();

    /**
     * @param array $input Validated input (matches the input schema).
     * @return array
     * @throws Exception
     */
    public function execute(array $input);
}
