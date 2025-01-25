<?php

namespace App\Managers;

use App\Core\DB\DatabaseRow;
use App\Logger\Logger;
use App\Repositories\UserSubstituteRepository;

/**
 * UserSubstituteManager is used for handling user substitution
 * 
 * @author Lukas Velek
 */
class UserSubstituteManager extends AManager {
    private UserSubstituteRepository $userSubstituteRepository;

    private array $mUserSubstituteCache;

    public function __construct(Logger $logger, EntityManager $entityManager, UserSubstituteRepository $userSubstituteRepository) {
        parent::__construct($logger, $entityManager);

        $this->userSubstituteRepository = $userSubstituteRepository;

        $this->mUserSubstituteCache = [];
    }

    /**
     * Checks if given user has a substitute
     * 
     * @param string $userId User ID
     */
    public function hasUserSubstitute(string $userId): bool {
        if(!array_key_exists($userId, $this->mUserSubstituteCache)) {
            $this->mUserSubstituteCache[$userId] = $this->userSubstituteRepository->getUserSubstitute($userId);
        }

        return $this->mUserSubstituteCache[$userId] !== null;
    }

    /**
     * Returns given user's substitute
     * 
     * @param string $userId User ID
     */
    public function getUserSubstitute(string $userId): ?DatabaseRow {
        if(!array_key_exists($userId, $this->mUserSubstituteCache)) {
            $this->mUserSubstituteCache[$userId] = $this->userSubstituteRepository->getUserSubstitute($userId);
        }

        if($this->mUserSubstituteCache[$userId] !== null) {
            return DatabaseRow::createFromDbRow($this->mUserSubstituteCache[$userId]);
        } else {
            return null;
        }
    }
}

?>