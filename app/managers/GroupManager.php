<?php

namespace App\Managers;

use App\Constants\SystemGroups;
use App\Logger\Logger;
use App\Repositories\GroupMembershipRepository;
use App\Repositories\GroupRepository;

class GroupManager extends AManager {
    private GroupRepository $gr;
    private GroupMembershipRepository $gmr;

    public function __construct(Logger $logger, EntityManager $em, GroupRepository $gr, GroupMembershipRepository $gmr) {
        parent::__construct($logger, $em);

        $this->gr = $gr;
        $this->gmr = $gmr;
    }

    public function isUserMemberOfSuperadministrators(string $userId) {
        $group = $this->gr->getGroupByTitle(SystemGroups::SUPERADMINISTRATORS);

        $members = $this->gmr->getMemberUserIdsForGroupId($group->getId());

        return in_array($userId, $members);
    }
}

?>