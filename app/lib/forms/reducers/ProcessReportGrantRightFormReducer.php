<?php

namespace App\Lib\Forms\Reducers;

use App\Constants\Container\ReportRightEntityType;
use App\Constants\Container\ReportRightOperations;
use App\Constants\Container\SystemGroups;
use App\UI\FormBuilder2\ABaseFormReducer;
use App\UI\FormBuilder2\FormState\FormStateList;

class ProcessReportGrantRightFormReducer extends ABaseFormReducer {
    public function applyReducer(FormStateList &$stateList) {
        if($stateList->getCallingElementName() == 'entityType') {
            $entities = $this->getEntitiesByType($stateList->entityType->value);
            
            if(count($entities) > 0) {
                $stateList->entityId->selectValues = $entities;
                $stateList->entityId->isDisabled = false;

                $rights = $this->getAvailableOperationsForEntity($entities[0]['value'], $stateList->entityType->value);;

                $stateList->operation->selectValues = $rights;
            } else {
                $stateList->entityId->selectvalues = [];
                $stateList->entityId->isDisabled = true;
                $stateList->operation->selectValues = [];
                $stateList->operation->isDisabled = true;
            }
        }
    }

    public function applyOnStartupReducer(FormStateList &$stateList) {
        $entities = $this->getEntitiesByType($stateList->entityType->value ?? ReportRightEntityType::USER);
        $stateList->entityId->selectValues = $entities;

        if(count($entities) > 0) {
            $stateList->entityId->isDisabled = false;
        } else {
            $stateList->entityId->selectValues = [
                [
                    'value' => 'null',
                    'text' => 'Empty'
                ]
            ];
            $stateList->entityId->isDisabled = true;
        }
    }

    public function applyAfterSubmitOnOpenReducer(FormStateList &$stateList) {}

    private function getEntitiesByType(int $type) {
        if($type == ReportRightEntityType::USER) {
            $usersDb = $this->container->getContainerUsers();

            $users = [];
            foreach($usersDb as $user) {
                $rights = $this->container->processReportManager->getReportRightsForUser($this->request->get('reportId'), $user->getId());

                if(count($rights) < count(ReportRightOperations::getAll())) {
                    $users[] = [
                        'value' => $user->getId(),
                        'text' => $user->getFullname()
                    ];
                }
            }

            return $users;
        } else {
            $groupsDbQb = $this->container->groupRepository->composeQueryForGroups();
            $groupsDbQb->execute();

            $groups = [];
            while($row = $groupsDbQb->fetchAssoc()) {
                $rights = $this->container->processReportManager->getReportRightsForGroup($this->request->get('reportId'), $row['groupId']);

                if(count($rights) < count(ReportRightOperations::getAll())) {
                    $groups[] = [
                        'value' => $row['groupId'],
                        'text' => SystemGroups::toString($row['title'])
                    ];
                }
            }

            return $groups;
        }
    }

    private function getAvailableOperationsForEntity(string $entityId, int $type) {
        if($type == ReportRightEntityType::USER) {
            $rightsDb = $this->container->processReportManager->getReportRightsForUser($this->request->get('reportId'), $entityId);

            $rights = [];
            foreach(ReportRightOperations::getAll() as $key => $value) {
                if(!in_array($key, $rightsDb)) {
                    $rights[] = [
                        'value' => $key,
                        'text' => $value
                    ];
                }
            }

            return $rights;
        } else {
            $rightsDb = $this->container->processReportManager->getReportRightsForGroup($this->request->get('reportId'), $entityId);

            $rights = [];
            foreach(ReportRightOperations::getAll() as $key => $value) {
                if(!in_array($key, $rightsDb)) {
                    $rights[] = [
                        'value' => $key,
                        'text' => $value
                    ];
                }
            }

            return $rights;
        }
    }
}