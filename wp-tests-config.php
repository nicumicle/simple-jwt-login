<?php

define('DB_NAME',     getenv('WORDPRESS_DB_NAME')     ?: 'wordpress_test');
define('DB_USER',     getenv('WORDPRESS_DB_USER')     ?: 'wordpress');
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: 'wordpress');
define('DB_HOST',     getenv('WORDPRESS_DB_HOST')     ?: 'wpdb:3308');
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  '');

$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

define('ABSPATH',          '/var/www/html/');
define('WP_TESTS_DOMAIN',  'localhost');
define('WP_TESTS_EMAIL',   'admin@example.org');
define('WP_TESTS_TITLE',   'Test Blog');
define('WP_PHP_BINARY',    'php');
define('WPLANG',           '');
