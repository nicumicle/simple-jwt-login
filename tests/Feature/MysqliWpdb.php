<?php

namespace SimpleJwtLoginTests\Feature;

use mysqli;

/**
 * Minimal $wpdb adapter backed by a raw mysqli connection.
 *
 * Implements only the subset of wpdb used by ApiKeyRepository:
 *   insert(), update(), prefix, insert_id.
 *
 * Format specifier arguments accepted by wpdb::insert / wpdb::update are
 * omitted here because this adapter escapes values itself.
 * PHP silently discards extra positional arguments on user-defined functions,
 * so callers that pass the format array will still work correctly.
 *
 * This exists so Feature tests can use ApiKeyRepository without bootstrapping
 * WordPress. It is NOT a general-purpose wpdb emulator.
 */
class MysqliWpdb
{
    /**
     * Table prefix, forwarded from the test environment variable.
     * @var string
     */
    public $prefix;

    /**
     * Auto-incremented ID from the last INSERT, mirrors wpdb::$insert_id.
     * @var integer
     */
    public $insert_id = 0;

    /**
     * @var mysqli
     */
    private $conn;

    /**
     * @param mysqli $conn
     * @param string $prefix
     */
    public function __construct(mysqli $conn, string $prefix)
    {
        $this->conn   = $conn;
        $this->prefix = $prefix;
    }

    /**
     * @param string $table
     * @param array  $data
     * @return int|false  Rows affected, or false on error.
     */
    public function insert(string $table, array $data)
    {
        $cols = array_keys($data);
        $vals = array_values($data);

        $colsSql = implode(', ', array_map(static function ($col) {
            return "`{$col}`";
        }, $cols));

        $valsSql = implode(', ', array_map(function ($val) {
            return $val === null ? 'NULL' : "'" . $this->conn->real_escape_string((string) $val) . "'";
        }, $vals));

        $this->conn->query("INSERT INTO `{$table}` ({$colsSql}) VALUES ({$valsSql})");

        if ($this->conn->errno !== 0) {
            return false;
        }

        $this->insert_id = (int) $this->conn->insert_id;

        return $this->conn->affected_rows;
    }

    /**
     * Mirrors wpdb::get_charset_collate().
     *
     * @return string
     */
    public function get_charset_collate()
    {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    /**
     * Run an arbitrary SQL statement.
     * Mirrors wpdb::query() — returns the number of rows affected, or false on error.
     *
     * @param string $sql
     * @return int|false
     */
    public function query(string $sql)
    {
        $result = $this->conn->query($sql);
        if ($result === false) {
            return false;
        }

        return $this->conn->affected_rows;
    }

    /**
     * @param string $table
     * @param array  $data
     * @param array  $where
     * @return int|false  Rows affected, or false on error.
     */
    public function update(string $table, array $data, array $where)
    {
        $setClauses = array_map(function ($key, $value) {
            $val = $value === null ? 'NULL' : "'" . $this->conn->real_escape_string((string) $value) . "'";
            return "`{$key}` = {$val}";
        }, array_keys($data), array_values($data));

        $whereClauses = array_map(function ($key, $value) {
            $val = $value === null ? 'NULL' : "'" . $this->conn->real_escape_string((string) $value) . "'";
            return "`{$key}` = {$val}";
        }, array_keys($where), array_values($where));

        $this->conn->query(
            'UPDATE `' . $table . '` SET ' . implode(', ', $setClauses)
            . ' WHERE ' . implode(' AND ', $whereClauses)
        );

        return $this->conn->errno === 0 ? $this->conn->affected_rows : false;
    }
}
