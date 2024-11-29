<?php

namespace App\Enums;

use App\Core\Datatypes\ArrayList;
use App\Core\DB\DatabaseRow;
use App\Managers\GroupManager;
use App\Repositories\UserRepository;

/**
 * Metadata user enum contains all users in container
 * 
 * @author Lukas Velek
 */
class MetadataUserEnum extends AEnumForMetadata {
    private UserRepository $userRepository;
    private GroupManager $groupManager;
    private DatabaseRow $container;

    /**
     * Class constructor
     * 
     * @param UserRepository $userRepository UserRepository instance
     * @param GroupManager $groupManager GroupManager instance
     * @param DatabaseRow $container Container DB row
     */
    public function __construct(UserRepository $userRepository, GroupManager $groupManager, DatabaseRow $container) {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->groupManager = $groupManager;
        $this->container = $container;
    }

    public function getAll(): ArrayList {
        if($this->cache->isEmpty()) {
            $this->cache->add('null', [self::KEY => 'null', self::TITLE => '-']);

            $containerUsers = $this->groupManager->getGroupUsersForGroupTitle($this->container->title . ' - users');

            $qb = $this->userRepository->composeQueryForUsers();
            $qb->andWhere('username <> ?', ['service_user']);
            $qb->andWhere($qb->getColumnInValues('userId', $containerUsers));
            $qb->execute();

            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $this->cache->add($row->userId, [
                    self::KEY => $row->fullname,
                    self::TITLE => $row->fullname
                ]);
            }
        }

        return $this->cache;
    }
}

?>