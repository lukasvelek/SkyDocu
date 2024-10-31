<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Exceptions\EntityUpdateException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

class UserManager extends AManager {
    private UserRepository $userRepository;

    public function __construct(
        Logger $logger,
        UserRepository $userRepository,
        EntityManager $entityManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->userRepository = $userRepository;
    }

    public function getUserByUsername(string $username) {
        $user = $this->userRepository->getUserByUsername($username);

        if($user === null) {
            throw new NonExistingEntityException('User with username \'' . $username . '\' does not exist.');
        }

        return $user;
    }

    public function getUserById(string $userId) {
        $user = $this->userRepository->getUserById($userId);

        if($user === null) {
            throw new NonExistingEntityException('User with ID \'' . $userId . '\' does not exist.');
        }

        return $user;
    }

    public function createNewUser(string $username, string $fullname, string $password, ?string $email) {
        $userId = $this->createId(EntityManager::USERS);

        if(!$this->userRepository->createNewUser($userId, $username, $password, $fullname, $email)) {
            throw new GeneralException('Could not create user.');
        }

        return $userId;
    }
}

?>