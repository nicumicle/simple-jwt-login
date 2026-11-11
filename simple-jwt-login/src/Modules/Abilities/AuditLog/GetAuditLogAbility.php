<?php

namespace SimpleJWTLogin\Modules\Abilities\AuditLog;

use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;

class GetAuditLogAbility extends AbstractAbility
{
    const MAX_PER_PAGE = 100;

    /**
     * @var string[]
     */
    private static $filterKeys = array('event_type', 'status', 'user_id', 'user_email', 'date_from', 'date_to');

    /**
     * @var AuditLogRepositoryInterface
     */
    private $auditLogRepository;

    /**
     * @param SimpleJWTLoginSettings      $jwtSettings
     * @param AuditLogRepositoryInterface $auditLogRepository
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, AuditLogRepositoryInterface $auditLogRepository)
    {
        parent::__construct($jwtSettings);
        $this->auditLogRepository = $auditLogRepository;
    }

    protected function getSlug()
    {
        return 'get-audit-log';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        $dateSchema = ['type' => 'string', 'pattern' => '^\d{4}-\d{2}-\d{2}$'];

        return [
            'label'         => __('Get the Simple JWT Login audit log', 'simple-jwt-login'),
            'description'   => __(
                'Returns audit log entries (login, register, token, API key, settings and AI ability events), newest first, with optional filters. Entries are only recorded for events enabled in the Audit Log settings.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'event_type' => ['type' => 'string', 'enum' => AuditEvents::all()],
                'status'     => ['type' => 'string', 'enum' => ['success', 'failure']],
                'user_id'    => ['type' => 'integer', 'minimum' => 1],
                'user_email' => ['type' => 'string'],
                'date_from'  => $dateSchema,
                'date_to'    => $dateSchema,
                'page'       => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                'per_page'   => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_PER_PAGE, 'default' => 20],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'items'    => ['type' => 'array'],
                    'total'    => ['type' => 'integer'],
                    'page'     => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $page    = max(1, (int) $this->inputValue($input, 'page', 1));
        $perPage = max(1, min(self::MAX_PER_PAGE, (int) $this->inputValue($input, 'per_page', 20)));

        $filters = [];
        foreach (self::$filterKeys as $key) {
            if (isset($input[$key]) && $input[$key] !== '') {
                $filters[$key] = $input[$key];
            }
        }

        $result = $this->auditLogRepository->findPaginated($filters, $page, $perPage);

        return [
            'items'    => array_values(array_map(array($this, 'formatEntry'), (array) $result['items'])),
            'total'    => (int) $result['total'],
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param object|array $entry
     * @return array
     */
    protected function formatEntry($entry)
    {
        $row = (array) $entry;

        return [
            'id'         => isset($row['id']) ? (int) $row['id'] : 0,
            'event_type' => isset($row['event_type']) ? (string) $row['event_type'] : '',
            'status'     => isset($row['status']) ? (string) $row['status'] : '',
            'user_id'    => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'user_email' => isset($row['user_email']) ? (string) $row['user_email'] : null,
            'ip_address' => isset($row['ip_address']) ? (string) $row['ip_address'] : null,
            'message'    => isset($row['message'])
                ? (new SettingsRedactor(new AbilitySettingsPolicy()))->redactAuditMessage((string) $row['message'])
                : null,
            'api_key_id' => isset($row['api_key_id']) ? (int) $row['api_key_id'] : null,
            'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
        ];
    }
}
