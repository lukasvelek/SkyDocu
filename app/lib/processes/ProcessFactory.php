<?php

namespace App\Lib\Processes;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Constants\Container\SystemProcessTypes;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Lib\Processes\Shredding\ShreddingRequestProcess;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;

/**
 * ProcessFactory is used for creating Process instances
 * 
 * @author Lukas Velek
 */
class ProcessFactory {
    private DocumentManager $documentManager;
    private DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    private GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    private UserManager $userManager;
    private GroupManager $groupManager;
    private UserEntity $currentUser;
    public ProcessManager $processManager;

    private string $containerId;

    /**
     * Class constructor
     * 
     * @param DocumentManager $documentManager DocumentManager instance
     * @param GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator GroupStandardOperationsAuthorizator instance
     * @param DocumentBulkActionAuthorizator $documentBulkActionAuthorizator DocumentBulkActionAuthorizator instance
     * @param UserManager $userManager UserManager instance
     * @param GroupManager $groupManager GroupManager instance
     * @param UserEntity $currentUser Current user UserEntity instance
     * @param ProcessManager $processManager ProcessManager instance
     * @param string $containerId Container ID
     */
    public function __construct(
        DocumentManager $documentManager,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        UserManager $userManager,
        GroupManager $groupManager,
        UserEntity $currentUser,
        string $containerId,
        ProcessManager $processManager
    ) {
        $this->documentManager = $documentManager;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
        $this->containerId = $containerId;
        $this->processManager = $processManager;
    }

    /**
     * Internal method for creating document AProcess instances
     * 
     * @param string $class Class name (e.g., ShreddingProcess::class)
     * @param array $args Class arguments
     * @return AProcess Class instance
     */
    private function commonDocumentCreate(string $class, array $args = []) {
        /**
         * @var AProcess $obj
         */
        $obj = new $class(...$args);
        $obj->inject(
            $this->documentManager,
            $this->groupStandardOperationsAuthorizator,
            $this->documentBulkActionAuthorizator,
            $this->userManager,
            $this->groupManager,
            $this->currentUser,
            $this->processManager
        );
        $obj->setContainerId($this->containerId);
        $obj->startup();
        return $obj;
    }

    /**
     * @return ShreddingProcess
     */
    public function createDocumentShreddingProcess() {
        $obj = $this->commonDocumentCreate(ShreddingProcess::class);
        return $obj;
    }

    /**
     * @return ShreddingRequestProcess
     */
    public function createDocumentShreddingRequestProcess() {
        $obj = $this->commonDocumentCreate(ShreddingRequestProcess::class);
        return $obj;
    }

    /**
     * Starts a document process synchronously
     * 
     * @param string $name Process name
     * @param array $documentIds Document IDs
     * @param array $exceptions Exceptions thrown
     * @return bool True on success or false on failure
     */
    public function startDocumentProcess(string $name, array $documentIds, array &$exceptions) {
        switch($name) {
            case SystemProcessTypes::SHREDDING:
                $obj = $this->createDocumentShreddingProcess();
                return $obj->execute($documentIds, $this->currentUser->getId(), $exceptions);

            case SystemProcessTypes::SHREDDING_REQUEST:
                $obj = $this->createDocumentShreddingRequestProcess();
                return $obj->execute($documentIds, $this->currentUser->getId(), $exceptions);

            default:
                throw new GeneralException('Process with name \'' . $name . '\' does not exist.');
        }
    }
}

?>