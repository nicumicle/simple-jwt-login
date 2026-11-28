<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\AuditLoggerService;

/**
 * Records WP-CLI commands that change something (cli.command.success / cli.command.failed).
 *
 * The audit settings are read before the command runs: a command that turns the audit log off
 * is still recorded. A command that turns it on is recorded too.
 */
class CliAuditLog
{
    /**
     * @var AuditLogRepositoryInterface
     */
    private $repository;

    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    /**
     * @var AuditLogSettings|null
     */
    private $before;

    /**
     * @var integer|null The WP-CLI --user, read before a command switches the current user.
     */
    private $userId;

    /**
     * @param AuditLogRepositoryInterface $repository
     * @param WordPressDataInterface      $wordPressData
     */
    public function __construct(AuditLogRepositoryInterface $repository, WordPressDataInterface $wordPressData)
    {
        $this->repository = $repository;
        $this->wordPressData = $wordPressData;
    }

    /**
     * Call before the command changes anything.
     *
     * @return void
     */
    public function start()
    {
        $this->before = $this->loadAuditSettings();
        $this->userId = (int) $this->wordPressData->getCurrentUserId();
    }

    /**
     * @param string $command e.g. "config set"
     * @param string $status  "success" or "failure"
     * @param array  $details Never secrets.
     * @return void
     */
    public function record($command, $status, array $details = array())
    {
        $event = $status === 'success' ? AuditEvents::CLI_COMMAND_SUCCESS : AuditEvents::CLI_COMMAND_FAILED;
        $settings = $this->before !== null && $this->before->isAuditEventEnabled($event)
            ? $this->before
            : $this->loadAuditSettings();

        $userId = $this->userId !== null ? $this->userId : (int) $this->wordPressData->getCurrentUserId();
        $logger = new AuditLoggerService($this->repository, $settings, new ServerHelper(array()), $this->wordPressData);
        $logger->log(
            $event,
            $userId > 0 ? $userId : null,
            null,
            $status,
            (string) wp_json_encode(array_merge(array('source' => 'cli', 'command' => 'wp jwt ' . $command), $details))
        );
    }

    /**
     * Fresh from the database: commands such as `config set` save the option directly.
     *
     * @return AuditLogSettings
     */
    protected function loadAuditSettings()
    {
        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressData);
        $jwtSettings->reload();

        return $jwtSettings->getAuditLogSettings();
    }
}
