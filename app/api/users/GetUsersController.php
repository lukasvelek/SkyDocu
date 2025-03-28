<?php

namespace App\Api\Users;

use App\Api\AAuthenticatedApiController;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetUsersController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('userId', $this->data)) {
            // single

            $user = $this->getUser($this->get('userId'));

            foreach($properties as $property) {
                if(!$this->checkProperty($property)) continue;
                
                $results[$property] = $user->$property;
            }

            $this->logRead(false, ExternalSystemLogObjectTypes::USER);
        } else {
            $users = $this->getUsers($this->get('limit'), $this->get('offset'));

            foreach($users as $user) {
                foreach($properties as $property) {
                    if(!$this->checkProperty($property)) continue;

                    $results[$user->userId][$property] = $user->$property;
                }
            }

            $this->logRead(true, ExternalSystemLogObjectTypes::USER);
        }

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Checks if property is enabled
     * 
     * @param string $name Property name
     */
    private function checkProperty(string $name): bool {
        return in_array($name, [
            'userId',
            'username',
            'fullname',
            'dateCreated',
            'email',
            'isTechnical',
            'appDesignTheme'
        ]);
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