<?php

namespace App\Repositories;

use App\Core\Application;
use App\Core\Caching\CacheNames;
use App\Entities\UserEntity;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;
use QueryBuilder\QueryBuilder;

class UserRepository extends ARepository {
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

    public function getUserForAuthentication(string $email) {
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

    public function getUserByEmail(string $email): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('users')
            ->where('email = ?', [$email])
            ->execute();

        return $qb->fetch();
    }

    public function updateUser(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set($data)
            ->where('userId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }

    public function searchUsers(string $value, array $columns, array $exceptUsers = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->andWhere('email <> ?', [Application::SERVICE_USER_EMAIL]);

        $sql = [];
        foreach($columns as $col) {
            $sql[] = sprintf('%s = %s', $col, $value);
        }

        $qb->andWhere('(' . implode(' OR ', $sql) . ')');
        
        if(!empty($exceptUsers)) {
            $qb->andWhere($qb->getColumnNotInValues('userId', $exceptUsers));
        }

        $qb->execute();

        return $this->createUsersArrayFromQb($qb);
    }

    public function createNewUser2(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('users', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    public function createNewUser(string $id, string $email, string $password, string $fullname) {
        $qb = $this->qb(__METHOD__);

        $keys = ['userId', 'email', 'password', 'fullname'];
        $values = [$id, $email, $password, $fullname];

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

    public function get(QueryOperation $operation): QueryResult {
        return $this->processPeeQL('users', $operation);
    }

    public function getUserByLoginHash(string $loginHash) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('users')
            ->where('loginHash = ?', [$loginHash])
            ->execute();

        return $qb->fetch();
    }
}

?>