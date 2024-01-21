<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>

<div class="row">
    <h3 class="section-title">Applications</h3>
</div>
<hr/>

<div class="application">
    <?php
    include_once "applications/google.php";
    ?>
</div>
