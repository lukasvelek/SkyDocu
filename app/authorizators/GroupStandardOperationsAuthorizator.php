<?php

namespace App\Authorizators;

use App\Constants\Container\GroupStandardOperationRights;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;

class GroupStandardOperationsAuthorizator extends AAuthorizator {
    private GroupManager $gm;
    
    public function __construct(DatabaseConnection $db, Logger $logger, GroupManager $gm) {
        parent::__construct($db, $logger);

        $this->gm = $gm;
    }

    public function canUserShareDocuments(string $userId) {
        return $this->commonCanUserX($userId, GroupStandardOperationRights::CAN_SHARE_DOCUMENTS);
    }

    private function commonCanUserX(string $userId, string $rightName) {
        $userGroups = $this->gm->getGroupsForUser($userId);

        $totalRights = [
            GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS => false,
            GroupStandardOperationRights::CAN_SHARE_DOCUMENTS => false,
            GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY => false
        ];

        foreach($userGroups as $groupId) {
            $share = $this->gm->canGroupShareDocuments($groupId);

            if($share === true && $totalRights[GroupStandardOperationRights::CAN_SHARE_DOCUMENTS] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_SHARE_DOCUMENTS] = true;
            }

            $export = $this->gm->canGroupExportDocuments($groupId);

            if($export === true && $totalRights[GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS] = true;
            }

            $viewDocumentHistory = $this->gm->canGroupViewDocumentHistory($groupId);

            if($viewDocumentHistory === true && $totalRights[GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY] !== true) {
                $totalRights[GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY] = true;
            }
        }

        return $totalRights[$rightName];
    }
}

?>