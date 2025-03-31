<?php

namespace App\Api\Users;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetUsersController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_USERS)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'userId',
            'username',
            'fullname',
            'dateCreated',
            'email',
            'isTechnical',
            'appDesignTheme'
        ]);

        $results = $this->getResults([$this, 'getUsers'], 'userId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::USER);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of users
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    protected function getUsers(int $limit, int $offset): array {
        $container = $this->app->containerManager->getContainerById($this->containerId, true);

        $userIds = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        $qb = $this->app->userRepository->composeQueryForUsers();

        $this->appendWhereConditions($qb);

        $qb->andWhere($qb->getColumnInValues('userId', $userIds))
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = DatabaseRow::createFromDbRow($row);
        }

        return $users;
    }
}

?>