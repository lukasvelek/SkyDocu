<?php

namespace App\Api\Users;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetUsersController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'userId',
            'username',
            'fullname',
            'dateCreated',
            'email',
            'isTechnical',
            'appDesignTheme'
        ]);

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $users = $this->getUsers($this->get('limit'), $this->get('offset'));

        foreach($users as $user) {
            foreach($properties as $property) {
                $results[$user->userId][$property] = $user->$property;
            }
        }

        $this->logRead(true, ExternalSystemLogObjectTypes::USER);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of users
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getUsers(int $limit, int $offset): array {
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