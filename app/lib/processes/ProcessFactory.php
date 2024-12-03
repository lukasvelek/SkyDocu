<?php

namespace App\Lib\Processes;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Entities\UserEntity;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
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
     * @param string $containerId Container ID
     */
    public function __construct(
        DocumentManager $documentManager,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        UserManager $userManager,
        GroupManager $groupManager,
        UserEntity $currentUser,
        string $containerId
    ) {
        $this->documentManager = $documentManager;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
        $this->containerId = $containerId;
    }

    /**
     * Internal method for creating AProcess instances
     * 
     * @param string $class Class name (e.g., ShreddingProcess::class)
     * @param array $args Class arguments
     * @return AProcess Class instance
     */
    private function commonCreate(string $class, array $args = []) {
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
            $this->currentUser
        );
        $obj->setContainerId($this->containerId);
        $obj->startup();
        return $obj;
    }

    /**
     * @return ShreddingProcess
     */
    public function createShreddingProcess() {
        $obj = $this->commonCreate(ShreddingProcess::class);
        return $obj;
    }
}

?>