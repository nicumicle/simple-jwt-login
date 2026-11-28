<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Services\ApiKeys\CreateApiKeyService;

/**
 * Manage API keys.
 */
class ApiKeyCommand extends AbstractCommand
{
    /**
     * @var string[]
     */
    private static $fields = array('id', 'user_id', 'name', 'key_prefix', 'permissions', 'expires_at', 'last_used_at', 'revoked_at');

    /**
     * Create an API key for a user. The key is shown only once.
     *
     * The owner's capabilities apply: a user can only grant the permissions they have.
     *
     * ## OPTIONS
     *
     * <owner>
     * : User ID, login or email of the key owner.
     *
     * --name=<name>
     * : A name for the key.
     *
     * --permissions=<permissions>
     * : Comma separated permissions: read, create, update, delete. For example read,create.
     *
     * [--expires-at=<date>]
     * : Expiration date (Y-m-d H:i:s, UTC). Default: never.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt api-key create admin --name="CI deploy" --permissions=read
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function create(array $args, array $assocArgs)
    {
        $format = $this->getFormat($assocArgs);
        $request = array(
            'name'        => isset($assocArgs['name']) ? $assocArgs['name'] : '',
            'permissions' => isset($assocArgs['permissions'])
                ? array_values(array_filter(array_map('trim', explode(',', $assocArgs['permissions']))))
                : array(),
            'expires_at'  => isset($assocArgs['expires-at']) ? $assocArgs['expires-at'] : null,
        );

        try {
            $owner = $this->findUser(isset($args[0]) ? $args[0] : '');
            $ownerId = (int) $this->createWordPressData()->getUserProperty($owner, 'ID');
            $key = $this->audited('api-key create', function () use ($owner, $request) {
                return $this->createKey($owner, $request);
            }, array('user_id' => $ownerId, 'name' => $request['name']));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $this->outputKeyValue($key, $format);
        if ($format === self::FORMAT_TEXT) {
            \WP_CLI::warning(__('Copy the key now: it is not shown again.', 'simple-jwt-login'));
        }
    }

    /**
     * List API keys of all users. Keys are never shown, only their prefix.
     *
     * ## OPTIONS
     *
     * [--page=<page>]
     * : Page number. Default: 1.
     *
     * [--per-page=<count>]
     * : Keys per page. Default: 100.
     *
     * [--fields=<fields>]
     * : Comma separated fields to show.
     *
     * [--format=<format>]
     * : table, csv, json, yaml or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp jwt api-key list --format=json
     *
     * @subcommand list
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function listKeys(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $page = isset($assocArgs['page']) ? max(1, (int) $assocArgs['page']) : 1;
        $perPage = isset($assocArgs['per-page']) ? max(1, (int) $assocArgs['per-page']) : 100;

        $result = $this->createApiKeyRepository()->findAll($page, $perPage);
        $items = array();
        foreach ((array) $result['items'] as $item) {
            // findAll() returns every column: copy the listed ones only, never key_hash.
            $row = array_intersect_key((array) $item, array_flip(self::$fields));
            $row['key_prefix'] .= '****';
            $row['permissions'] = (array) json_decode((string) $row['permissions'], true);
            $items[] = $row;
        }

        $this->outputItems($items, $assocArgs, self::$fields);
    }

    /**
     * Revoke an API key. It stays in the list as revoked.
     *
     * ## OPTIONS
     *
     * <id>
     * : The key ID (from `wp jwt api-key list`).
     *
     * ## EXAMPLES
     *
     *     wp jwt api-key revoke 3
     *
     * @param array $args
     * @return void
     */
    public function revoke(array $args)
    {
        $keyId = isset($args[0]) ? (int) $args[0] : 0;
        $repository = $this->createApiKeyRepository();
        if ($keyId < 1 || $repository->findById($keyId) === null) {
            \WP_CLI::error(__('API key not found.', 'simple-jwt-login'));
            return;
        }

        try {
            $this->audited('api-key revoke', function () use ($repository, $keyId) {
                if (!$repository->revokeById($keyId, gmdate('Y-m-d H:i:s'))) {
                    throw new Exception(esc_html__('Failed to revoke API key.', 'simple-jwt-login'));
                }
            }, array('id' => $keyId));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }
        \WP_CLI::success(__('API key revoked successfully.', 'simple-jwt-login'));
    }

    /**
     * @param mixed $owner   WP_User
     * @param array $request
     * @return array
     * @throws Exception
     */
    protected function createKey($owner, array $request)
    {
        // The key belongs to the current user, as when created from wp-admin.
        $this->createWordPressData()->setCurrentUser($owner);
        $response = (new CreateApiKeyService())
            ->withRequest($request)
            ->withSettings($this->createSettings())
            ->withApiKeyRepository($this->createApiKeyRepository())
            ->makeAction();
        $body = $response->get_data();

        return $body['data'];
    }

    /**
     * @return ApiKeyRepositoryInterface
     */
    protected function createApiKeyRepository()
    {
        global $wpdb;

        return new ApiKeyRepository($wpdb);
    }
}
