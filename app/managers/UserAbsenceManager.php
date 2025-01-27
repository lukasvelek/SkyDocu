<?php

namespace App\Managers;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\UserAbsenceRepository;

/**
 * UserAbsenceManager is used for handling user absence
 * 
 * @author Lukas Velek
 */
class UserAbsenceManager extends AManager {
    public UserAbsenceRepository $userAbsenceRepository;
    private array $mUserAbsenceCache;

    public function __construct(Logger $logger, EntityManager $entityManager, UserAbsenceRepository $userAbsenceRepository) {
        parent::__construct($logger, $entityManager);

        $this->userAbsenceRepository = $userAbsenceRepository;

        $this->mUserAbsenceCache = [];
    }

    /**
     * Checks if given user is absent or presenter
     * 
     * @param string $userId User ID
     */
    public function isUserAbsent(string $userId): bool {
        if(!array_key_exists($userId, $this->mUserAbsenceCache)) {
            $result = $this->userAbsenceRepository->getCurrentAbsenceForUser($userId);
            $this->mUserAbsenceCache[$userId] = $result;
        }

        return $this->mUserAbsenceCache[$userId] !== null;
    }

    /**
     * Returns an instance of DatabaseRow filled with data of current user's absence
     * 
     * @param string $userId User ID
     */
    public function getUserCurrentAbsence(string $userId): ?DatabaseRow {
        if(!array_key_exists($userId, $this->mUserAbsenceCache)) {
            $result = $this->userAbsenceRepository->getCurrentAbsenceForUser($userId);
            $this->mUserAbsenceCache[$userId] = $result;
        }

        if($this->mUserAbsenceCache[$userId] !== null) {
            return DatabaseRow::createFromDbRow($this->mUserAbsenceCache[$userId]);
        } else {
            return null;
        }
    }

    /**
     * Creates a new user absence
     * 
     * @param string $userId User ID
     * @param string $dateFrom Date from
     * @param string $dateTo Date to
     * @throws GeneralException
     */
    public function createUserAbsence(string $userId, string $dateFrom, string $dateTo): void {
        $absenceId = $this->createId(EntityManager::USER_ABSENCE);

        if(!$this->userAbsenceRepository->insertUserAbsence($absenceId, $userId, $dateFrom, $dateTo)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Updates given user absence
     * 
     * @param string $absenceId Absence ID
     * @param array $data Data to be written
     * @throws GeneralException
     */
    public function updateUserAbsence(string $absenceId, array $data): void {
        if(!$this->userAbsenceRepository->updateUserAbsence($absenceId, $data)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>