<?php

namespace App\Managers;

use App\Core\Application;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

class UserManager extends AManager {
    public UserRepository $userRepository;

    public function __construct(
        Logger $logger,
        UserRepository $userRepository
    ) {
        parent::__construct($logger);

        $this->userRepository = $userRepository;
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
     * @param string $email Email
     * @param string $password Password
     * @param string $containerName Container name
     * @throws GeneralException
     */
    public function createNewTechnicalUser(string $email, string $password, string $containerName): string {
        $userId = $this->createId();
        
        $containerName = strtolower($containerName);
        $containerName = preg_replace('/[^A-Za-z0-9]/', '_', $containerName);
        $containerName = substr($containerName, 0, 10);

        $emailForFullname = explode('@', $email)[0];

        $fullname = sprintf('%s_TechnicalUser_%s', $emailForFullname, $containerName);

        $data = [
            'userId' => $userId,
            'email' => $email,
            'password' => $password,
            'fullname' => $fullname,
            'isTechnical' => 1
        ];

        if(!$this->userRepository->createNewUser2($data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }

        return $userId;
    }

    /**
     * Creates a new user
     * 
     * @param string $email Email
     * @param string $fullname Fullname
     * @param string $password Password
     * @throws GeneralException
     */
    public function createNewUser(string $email, string $fullname, string $password): string {
        $userId = $this->createId();

        if(!$this->userRepository->createNewUser($userId, $email, $password, $fullname)) {
            throw new GeneralException('Could not create user.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }

        return $userId;
    }

    /**
     * Returns service user ID
     * 
     * @throws NonExistingEntityException
     */
    public function getServiceUserId(): string {
        $user = $this->userRepository->getUserByEmail(Application::SERVICE_USER_EMAIL);

        if($user === null) {
            throw new NonExistingEntityException('No service user exists.');
        }

        return $user['userId'];
    }

    public function updateUser(string $userId, array $data) {
        $data['dateModified'] = date('Y-m-d H:i:s');

        if(!$this->userRepository->updateUser($userId, $data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function deleteUser(string $userId) {
        if(!$this->userRepository->deleteUser($userId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USERS)) {
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

    /**
     * Returns user for given email
     * 
     * @param string $email Email
     */
    public function getUserByEmail(string $email): UserEntity {
        $row = $this->userRepository->getUserByEmail($email);

        if($row === null) {
            throw new NonExistingEntityException('No user found.');
        }

        return UserEntity::createEntityFromDbRow($row);
    }
}

?>