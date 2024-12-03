<?php

namespace App\Lib\Processes;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Entities\UserEntity;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
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

    protected string $containerId;

    private bool $useDbTransactions;

    /**
     * Injects common objects
     * 
     * @param DocumentManager $documentManager DocumentManager instance
     * @param GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator GroupStandardOperationsAuthorizator instance
     * @param DocumentBulkActionAuthorizator $documentBulkActionAuthorizator DocumentBulkActionAuthorizator instance
     * @param UserManager $userManager UserManager instance
     * @param GroupManager $groupManager GroupManager instance
     * @param UserEntity $currentUser Current user UserEntity instance
     */
    public function inject(
        DocumentManager $documentManager,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        UserManager $userManager,
        GroupManager $groupManager,
        UserEntity $currentUser
    ) {
        $this->documentManager = $documentManager;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
    }

    /**
     * Setups the object
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
     * Tests if current operation is executable on given array of document IDs for given user (or current user). This is useful for testing for bulk actions.
     * 
     * @param array $documentIds Array of Document IDs
     * @param ?string $userId User ID or null if current user should be used
     * @param array $exceptions Caught exceptions
     * @return bool True if operation is executable on all of the documents or false if not
     */
    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []) {}

    /**
     * Performs current operation on given array of document IDs
     * 
     * @param array $documentIds Array of Document IDs
     * @param ?string $userId User ID or null if current user should be used
     * @param array $exceptions Caught exceptions
     * @return bool True if operation was successful or false if not
     */
    public abstract function execute(array $documentIds, ?string $userId = null, array &$exceptions = []);
}

?>