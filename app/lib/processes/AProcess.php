<?php

namespace App\Lib\Processes;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Entities\UserEntity;
use App\Managers\Container\ArchiveManager;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;

/**
 * Common predecessor to all processes
 * 
 * @author Lukas Velek
 */
abstract class AProcess {
    protected DocumentManager $documentManager;
    protected GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    protected DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    protected UserManager $userManager;
    protected GroupManager $groupManager;
    protected UserEntity $currentUser;
    protected ProcessManager $processManager;
    protected ArchiveManager $archiveManager;

    protected string $containerId;

    private bool $useDbTransactions;

    /**
     * Class constructor
     */
    public function __construct() {}

    /**
     * Injects common objects
     * 
     * @param DocumentManager $documentManager DocumentManager instance
     * @param GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator GroupStandardOperationsAuthorizator instance
     * @param DocumentBulkActionAuthorizator $documentBulkActionAuthorizator DocumentBulkActionAuthorizator instance
     * @param UserManager $userManager UserManager instance
     * @param GroupManager $groupManager GroupManager instance
     * @param UserEntity $currentUser Current user UserEntity instance
     * @param ProcessManager $processManager ProcessManager instance
     * @param ArchiveManager $archiveManager ArchiveManager instance
     */
    public function inject(
        DocumentManager $documentManager,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        UserManager $userManager,
        GroupManager $groupManager,
        UserEntity $currentUser,
        ProcessManager $processManager,
        ArchiveManager $archiveManager
    ) {
        $this->documentManager = $documentManager;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
        $this->processManager = $processManager;
        $this->archiveManager = $archiveManager;
    }

    /**
     * Sets up the object
     */
    public function startup() {
        $this->useDbTransactions = true;
    }

    /**
     * Sets current container ID
     * 
     * @param string $containerId Current container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Enables database transactions
     */
    public function enableDbTransactions() {
        $this->useDbTransactions = true;
    }

    /**
     * Disables database transactions
     */
    public function disableDbTransactions() {
        $this->useDbTransactions = false;
    }

    /**
     * Performs current operation on given array of document IDs - usually for creating workflows -> starting processes
     * 
     * @param array $documentIds Array of Document IDs
     * @param ?string $userId User ID or null if current user should be used
     * @param array $exceptions Caught exceptions
     * @return bool True if operation was successful or false if not
     */
    public abstract function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool;
}

?>