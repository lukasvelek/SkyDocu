<?php

namespace App\Managers;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
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

    /**
     * Sets given user's substitute
     * 
     * @param string $userId User ID
     * @param string $substituteUserId Substitute user ID
     * @throws GeneralException
     */
    public function setUserAbstitute(string $userId, string $substituteUserId) {
        if($this->hasUserSubstitute($userId)) {
            // update
            if(!$this->userSubstituteRepository->updateUserSubstitute($userId, $substituteUserId)) {
                throw new GeneralException('Database error.');
            }
        } else {
            // insert
            $entryId = $this->createId(EntityManager::USER_SUBSTITUTES);
            if(!$this->userSubstituteRepository->insertUserSubstitute($entryId, $userId, $substituteUserId)) {
                throw new GeneralException('Database error.');
            }
        }
    }

    /**
     * Removes user's substitute
     * 
     * @param string $userId User ID
     * @throws GeneralException
     */
    public function removeUserSubstitute(string $userId) {
        if(!$this->userSubstituteRepository->removeUserSubstitute($userId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Gets user's substitute or if none exists returns the user
     * 
     * @param string $userId User ID
     * @return string User's substitute ID or user's ID
     */
    public function getUserOrTheirSubstitute(string $userId): string {
        $substitute = $this->getUserSubstitute($userId);

        return $substitute?->substituteUserId ?? $userId;
    }
}

?>