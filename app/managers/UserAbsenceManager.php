<?php

namespace App\Managers;

use App\Logger\Logger;
use App\Repositories\UserAbsenceRepository;

class UserAbsenceManager extends AManager {
    private UserAbsenceRepository $userAbsenceRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, UserAbsenceRepository $userAbsenceRepository) {
        parent::__construct($logger, $entityManager);

        $this->userAbsenceRepository = $userAbsenceRepository;
    }

    /**
     * Checks if given user is absent or presenter
     * 
     * @param string $userId User ID
     */
    public function isUserAbsent(string $userId): bool {
        $result = $this->userAbsenceRepository->getCurrentAbsenceForUser($userId);

        return $result !== null;
    }
}

?>