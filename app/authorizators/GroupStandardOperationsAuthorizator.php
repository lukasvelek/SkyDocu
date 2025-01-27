<?php

namespace App\Authorizators;

use App\Constants\Container\GroupStandardOperationRights;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;

/**
 * GroupStandardOperationsAuthorizator is used for checking if a group is allowed standard operations
 * 
 * @author Lukas Velek
 */
class GroupStandardOperationsAuthorizator extends AAuthorizator {
    private GroupManager $containerGroupManager;
    
    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db
     * @param Logger $logger
     * @param GroupManager $containerGroupManager
     */
    public function __construct(DatabaseConnection $db, Logger $logger, GroupManager $containerGroupManager) {
        parent::__construct($db, $logger);

        $this->containerGroupManager = $containerGroupManager;
    }

    /**
     * Checks if user can view document history
     * 
     * @param string $userId
     * @return bool True if user can or false if they cannot
     */
    public function canUserViewDocumentHistory(string $userId) {
        return $this->commonCanUserX($userId, GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY);
    }

    /**
     * Checks if user can export documents
     * 
     * @param string $userId
     * @return bool True if user can or false if they cannot
     */
    public function canUserExportDocuments(string $userId) {
        return $this->commonCanUserX($userId, GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS);
    }

    /**
     * Checks if user can share documents
     * 
     * @param string $userId
     * @return bool True if user can or false if they cannot
     */
    public function canUserShareDocuments(string $userId) {
        return $this->commonCanUserX($userId, GroupStandardOperationRights::CAN_SHARE_DOCUMENTS);
    }

    /**
     * Checks if user is able to perform given operation
     * 
     * @param string $userId
     * @param string $rightName Name of the operation right
     * @return bool True if user can or false if they cannot
     */
    private function commonCanUserX(string $userId, string $rightName) {
        $userGroups = $this->containerGroupManager->getGroupsForUser($userId);

        $totalRights = [
            GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS => false,
            GroupStandardOperationRights::CAN_SHARE_DOCUMENTS => false,
            GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY => false
        ];

        foreach($userGroups as $groupId) {
            $share = $this->containerGroupManager->canGroupShareDocuments($groupId);

            if($share === true && $totalRights[GroupStandardOperationRights::CAN_SHARE_DOCUMENTS] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_SHARE_DOCUMENTS] = true;
            }

            $export = $this->containerGroupManager->canGroupExportDocuments($groupId);

            if($export === true && $totalRights[GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS] = true;
            }

            $viewDocumentHistory = $this->containerGroupManager->canGroupViewDocumentHistory($groupId);

            if($viewDocumentHistory === true && $totalRights[GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY] = true;
            }
        }

        return $totalRights[$rightName];
    }
}

?>