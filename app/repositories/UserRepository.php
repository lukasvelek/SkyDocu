<?php

namespace App\Repositories;

use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Entities\UserEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class UserRepository extends ARepository {
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);
    }

    public function getUserById(string $id, bool $force = false): UserEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('userId = ?', [$id]);

        $userCache = $this->cacheFactory->getCache(CacheNames::USERS);

        $entity = $userCache->load($id, function() use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = UserEntity::createEntityFromDbRow($row);

            return $entity;
        }, [], $force);

        return $entity;
    }

    public function getUserRowById(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('users')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetch();
    }

    public function getUserForAuthentication(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username = ?', [$username])
            ->execute();

        return $qb;
    }

    public function getUserByEmail(string $email) {
        $qb = $this->getUserByEmailForAuthentication($email);

        return UserEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getUserByEmailForAuthentication(string $email) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('email = ?', [$email])
            ->execute();

        return $qb;
    }

    public function saveLoginHash(string $userId, string $hash) {
        return $this->updateUser($userId, ['loginHash' => $hash]);
    }

    public function getLoginHashForUserId(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['loginHash'])
            ->from('users')
            ->where('userId = ?', [$userId])
            ->execute();

        $loginHash = null;
        while($row = $qb->fetchAssoc()) {
            $loginHash = $row['loginHash'];
        }
        
        return $loginHash;
    }

    public function getUserByUsername(string $username): UserEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('users')
            ->where('username = ?', [$username]);

        $userUsername2IdCache = $this->cacheFactory->getCache(CacheNames::USERS_USERNAME_TO_ID_MAPPING);

        $userId = $userUsername2IdCache->load($username, function() use ($qb) {
            $qb->execute();

            return $qb->fetch('userId');
        });

        if($userId === null) {
            return $userId;
        }

        return $this->getUserById($userId);
    }

    public function updateUser(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set($data)
            ->where('userId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }

    public function getUsersByIdBulk(array $ids, bool $idAsKey = false, bool $returnUsernameAsValue = false) {
        $users = [];

        foreach($ids as $id) {
            $result = $this->getUserById($id);

            if($result !== null) {
                if($returnUsernameAsValue) {
                    $result = $result->getUsername();
                }
                
                if($idAsKey) {
                    $users[$id] = $result;
                } else {
                    $users[] = $result;
                }
            }
        }

        return $users;
    }

    public function searchUsersByUsername(string $username, array $exceptUsers = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username LIKE ?', ['%' . $username . '%'])
            ->andWhere('username <> ?', ['service_user']);
        
        if(!empty($exceptUsers)) {
            $qb->andWhere($qb->getColumnNotInValues('userId', $exceptUsers));
        }

        $qb->execute();

        return $this->createUsersArrayFromQb($qb);
    }

    public function searchUsersByFullname(string $fullname, array $exceptUsers = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('fullname LIKE ?', ['%' . $fullname . '%'])
            ->andWhere('username <> ?', ['service_user']);
        
        if(!empty($exceptUsers)) {
            $qb->andWhere($qb->getColumnNotInValues('userId', $exceptUsers));
        }

        $qb->execute();

        return $this->createUsersArrayFromQb($qb);
    }

    public function createNewUser(string $id, string $username, string $password, string $fullname, ?string $email) {
        $qb = $this->qb(__METHOD__);

        $keys = ['userId', 'username', 'password', 'fullname'];
        $values = [$id, $username, $password, $fullname];

        if($email !== null) {
            $keys[] = 'email';
            $values[] = $email;
        }

        $qb ->insert('users', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    private function createUsersArrayFromQb(QueryBuilder $qb) {
        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = UserEntity::createEntityFromDbRow($row);
        }

        return $users;
    }

    public function composeQueryForUsers() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('users')
            ->orderBy('fullname');

        return $qb;
    }

    public function deleteUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('users')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>