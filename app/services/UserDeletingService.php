<?php

namespace App\Services;

use App\Constants\Container\ReportRightEntityType;
use App\Core\Application;
use App\Core\Container;
use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use Exception;

/**
 * UserDeletingService is responsible for deleting data for softly deleted users
 * 
 * @author Lukas Velek
 */
class UserDeletingService extends AService {
    public function __construct(Application $app) {
        parent::__construct('UserDeleting', $app);
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $deletedUsers = $this->getAllDeletedUsersAfterRetentionPeriod();

        foreach($deletedUsers as $userId) {
            $this->deleteUser($userId);
        }

        $this->cacheFactory->invalidateAllCache();
    }

    /**
     * Returns an array of deleted users after retention period
     */
    private function getAllDeletedUsersAfterRetentionPeriod(): array {
        $lastDate = new DateTime();
        $lastDate->modify('-' . DELETED_USER_RETENTION_PERIOD . 'd');
        $lastDate = $lastDate->getResult();

        $qb = $this->app->userRepository->composeQueryForUsers();
        $qb->where('dateDeleted <= ?', [$lastDate])
            ->execute();

        $userIds = [];
        while($row = $qb->fetchAssoc()) {
            $userIds[] = $row['userId'];
        }

        return $userIds;
    }

    /**
     * Deletes all user data
     * 
     * @param string $userId User ID
     */
    private function deleteUser(string $userId) {
        try {
            $this->app->userAbsenceRepository->beginTransaction(__METHOD__);

            // delete user absence
            $this->app->userAbsenceRepository->deleteUserAbsenceForUser($userId);
            
            // delete user substitutes
            $this->app->userSubstituteRepository->removeUserSubstitute($userId);

            // delete user group memberships in containers
            $groups = $this->app->groupManager->getMembershipsForUser($userId, true);

            $containers = [];
            foreach($groups as $group) {
                if(str_ends_with($group->title, ' - users')) {
                    $containerTitle = substr($group->title, 0, (strlen($group->title) - strlen(' - users')));

                    $containers[] = $this->app->containerManager->getContainerByTitle($containerTitle, true);
                }
            }

            $this->deleteUserDataInContainers($userId, $containers);

            // delete user group memberships
            $this->app->groupManager->removeUserFromAllGroups($userId);

            // try move all users subordinates to users superior
            $subordinates = $this->app->userManager->getAllSubordinatesForUser($userId);

            $user = $this->app->userManager->getUserById($userId, true);
            $superiorUserId = $user->getSuperiorUserId();

            if($superiorUserId !== null) {
                foreach($subordinates as $subordinateUserId) {
                    $this->app->userManager->updateUser($subordinateUserId, [
                        'subordinateUserId' => $superiorUserId
                    ]);
                }
            }

            // delete user
            $this->app->userManager->deleteUser($userId);

            $this->app->userAbsenceRepository->commit($this->app->userManager->getServiceUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->userAbsenceRepository->rollback(__METHOD__);

            $this->logger->exception($e, __METHOD__);
        }
    }

    /**
     * Deletes all user's data in containers
     * 
     * @param string $userId User ID
     * @param array $containers Containers
     */
    private function deleteUserDataInContainers(string $userId, array $containers) {
        $user = $this->app->userManager->getUserById($userId, true);
        $superiorUserId = $user->getSuperiorUserId();

        if($superiorUserId === null) {
            $superiorUserId = $this->app->userSubstituteManager->getUserOrTheirSubstitute($userId);
        }

        if($superiorUserId == $userId) {
            $superiorUserId = null;
        }

        /**
         * @var \App\Entities\ContainerEntity $container
         */
        foreach($containers as $container) {
            $cnt = new Container($this->app, $container->getId());

            try {
                $cnt->documentRepository->beginTransaction(__METHOD__);

                if($superiorUserId === null) {
                    $superiorUserId = $container->getUserId();
                }

                // delete from all groups
                $cnt->groupManager->removeUserFromAllGroups($userId);

                // reassign all process instances to their superior or admin
                $this->reassignUserProcessInstances($cnt, $userId, $superiorUserId);

                // remove all report rights
                $qb = $cnt->processReportManager->composeQueryForAllVisibleReports($userId, false);
                $qb->execute();

                while($row = $qb->fetchAssoc()) {
                    $cnt->processReportManager->revokeAllReportRightsToEntity($row['reportId'], $userId, ReportRightEntityType::USER);
                }

                // remove all document sharing
                $sharedBy = $cnt->documentManager->getSharedDocumentsByUser($userId);
                $sharedFor = $cnt->documentManager->getSharedDocumentsForUser($userId);

                foreach($sharedBy as $documentId) {
                    $cnt->documentManager->unshareDocumentByUserId($documentId, $userId);
                }

                foreach($sharedFor as $documentId) {
                    $cnt->documentManager->unshareDocumentForUserId($documentId, $userId);
                }

                $cnt->documentRepository->commit($this->app->userManager->getServiceUserId(), __METHOD__);
            } catch(AException $e) {
                $cnt->documentRepository->rollback(__METHOD__);

                $this->logger->exception($e, __METHOD__);
            }
        }
    }

    /**
     * Reassigns user's process instances
     * 
     * @param Container $cnt Container
     * @param string $userId User ID
     * @param string $newUserId New user ID
     */
    private function reassignUserProcessInstances(Container $cnt, string $userId, string $newUserId) {
        $qb = $cnt->processInstanceRepository->commonComposeQuery();
        $qb->andWhere('currentOfficerId = ?', [$userId])
            ->execute();

        $instanceIds = [];
        while($row = $qb->fetchAssoc()) {
            $instanceIds[] = $row['instanceId'];
        }

        foreach($instanceIds as $instanceId) {
            $cnt->processInstanceManager->sysReassignProcessInstance($instanceId, $newUserId);
        }
    }
}

?>