<?php

namespace App\Managers;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

class UserManager extends AManager {
    public UserRepository $userRepository;

    public function __construct(
        Logger $logger,
        UserRepository $userRepository,
        EntityManager $entityManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->userRepository = $userRepository;
    }

    public function getUserByUsername(string $username, bool $saveFile = true) {
        $user = $this->userRepository->getUserByUsername($username);

        if($user === null) {
            throw new NonExistingEntityException('User with username \'' . $username . '\' does not exist.', null, $saveFile);
        }

        return $user;
    }

    public function getUserById(string $userId, bool $force = false) {
        $user = $this->userRepository->getUserById($userId, $force);

        if($user === null) {
            throw new NonExistingEntityException('User with ID \'' . $userId . '\' does not exist.');
        }

        return $user;
    }

    /**
     * Creates a new technical user
     * 
     * @param string $username Username
     * @param string $password Password
     * @param ?string $email Email
     * @param string $containerName Container name
     * @throws GeneralException
     */
    public function createNewTechnicalUser(string $username, string $password, ?string $email, string $containerName): string {
        $userId = $this->createId(EntityManager::USERS);
        
        $containerName = strtolower($containerName);
        $containerName = preg_replace('/[^A-Za-z0-9]/', '_', $containerName);
        $containerName = substr($containerName, 0, 10);

        $fullname = sprintf('%s_TechnicalUser_%s', $username, $containerName);

        $data = [
            'userId' => $userId,
            'username' => $username,
            'password' => $password,
            'fullname' => $fullname,
            'isTechnical' => 1
        ];

        if($email !== null && $email != '') {
            $data['email'] = $email;
        }

        if(!$this->userRepository->createNewUser2($data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS) || !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS_USERNAME_TO_ID_MAPPING)) {
            throw new GeneralException('Could not invalidate cache.');
        }

        return $userId;
    }

    /**
     * Creates a new technical user
     * 
     * @param string $username Username
     * @param string $fullname Fullname
     * @param string $password Password
     * @param ?string $email Email
     * @throws GeneralException
     */
    public function createNewUser(string $username, string $fullname, string $password, ?string $email): string {
        $userId = $this->createId(EntityManager::USERS);

        if(!$this->userRepository->createNewUser($userId, $username, $password, $fullname, $email)) {
            throw new GeneralException('Could not create user.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS) || !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS_USERNAME_TO_ID_MAPPING)) {
            throw new GeneralException('Could not invalidate cache.');
        }

        return $userId;
    }

    public function getServiceUserId() {
        $user = $this->userRepository->getUserByUsername('service_user');

        return $user->getId();
    }

    public function updateUser(string $userId, array $data) {
        $data['dateModified'] = date('Y-m-d H:i:s');

        if(!$this->userRepository->updateUser($userId, $data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS)
           || !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS_USERNAME_TO_ID_MAPPING)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function deleteUser(string $userId) {
        if(!$this->userRepository->deleteUser($userId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS) ||
           !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS_USERNAME_TO_ID_MAPPING)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getUserRowById(string $userId) {
        $user = $this->userRepository->getUserRowById($userId);

        if($user === null) {
            throw new NonExistingEntityException('No user found.');
        }

        return DatabaseRow::createFromDbRow($user);
    }

    public function searchUsersByUsernameAndFullname(string $query, array $exceptUsers = []): array {
        $users = [];
        $usernameEntities = $this->userRepository->searchUsersByUsername($query, $exceptUsers);
        $fullnameEntities = $this->userRepository->searchUsersByFullname($query, $exceptUsers);

        foreach($usernameEntities as $user) {
            $users[$user->getId()] = $user->getFullname();
        }
        foreach($fullnameEntities as $user) {
            $users[$user->getId()] = $user->getFullname();
        }

        return $users;
    }
}

?>