<?php

namespace App\Api\Users;

use App\Api\AAuthenticatedApiController;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetUsersController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('userId', $this->data)) {
            // single

            $user = $this->getUser($this->data['userId']);

            foreach($properties as $property) {
                $results[$property] = $user->$property;
            }
        } else {
            $users = $this->getUsers($this->get('limit'), $this->get('offset'));

            foreach($users as $user) {
                foreach($properties as $property) {
                    $results[$user->userId][$property] = $user->$property;
                }
            }
        }

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns a single user
     * 
     * @param string $userId User ID
     */
    private function getUser(string $userId): DatabaseRow {
        $container = $this->app->containerManager->getContainerById($this->containerId, true);

        $userIds = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        if(!in_array($userId, $userIds)) {
            throw new GeneralException('User does not exist.');
        }

        return $this->app->userManager->getUserRowById($userId);
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