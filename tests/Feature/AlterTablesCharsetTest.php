<?php

namespace SimpleJwtLoginTests\Feature;

/**
 * One-time migration: converts all plugin tables to utf8mb4_unicode_ci.
 * DELETE THIS FILE after running it once.
 */
class AlterTablesCharsetTest extends TestBase
{
    public function testAlterAllTablesCharset(): void
    {
        $prefix = self::getTablePrefix();
        $tables = [
            $prefix . 'simple_jwt_login_refresh_tokens',
            $prefix . 'simple_jwt_login_audit_logs',
            $prefix . 'simple_jwt_login_webhook_logs',
            $prefix . 'simple_jwt_login_api_keys',
        ];

        $errors = [];
        foreach ($tables as $table) {
            $exists = self::$dbCon->query(
                "SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' LIMIT 1"
            );
            if ($exists === false || $exists->num_rows === 0) {
                continue;
            }

            self::$dbCon->query(
                "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            if (self::$dbCon->errno !== 0) {
                $errors[] = $table . ': ' . self::$dbCon->error;
            }
        }

        $this->assertEmpty($errors, implode('; ', $errors));
    }
}
