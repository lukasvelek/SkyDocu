<?php

namespace App\Authenticators;

use App\Constants\SessionNames;
use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Exceptions\BadCredentialsException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\UserRepository;

/**
 * UserAuthenticator allows to authenticate a user
 * 
 * @author Lukas Velek
 */
class UserAuthenticator {
    private UserRepository $userRepository;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param UserRepository $userRepository UserRepository instance
     * @param Logger $logger Logger instance
     */
    public function __construct(UserRepository $userRepository, Logger $logger) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * Tries to login user with information provided. It checks for bad credentials, disabled account and banned account.
     * 
     * @param string $username Username of the user, who's trying to log in
     * @param string $password Password of the user, who's trying to log in
     * @return true
     * @throws GeneralException
     */
    public function loginUser(string $username, string $password) {
        $rows = $this->userRepository->getUserForAuthentication($username);

        $user = null;

        while($row = $rows->fetchAssoc()) {
            if(password_verify($password, $row['password'])) {
                $user = UserEntity::createEntityFromDbRow($row);

                break;
            }
        }

        if($user === null) {
            throw new GeneralException('Incorrect credentials entered.');
        }

        $_SESSION[SessionNames::USER_ID] = $user->getId();
        $_SESSION[SessionNames::USERNAME] = $user->getUsername();
        $_SESSION[SessionNames::FULLNAME] = $user->getFullname();

        $hash = HashManager::createHash(64);

        $hashSaveResult = $this->userRepository->saveLoginHash($user->getId(), $hash);
        if($hashSaveResult === false) {
            throw new GeneralException('Could not save the generated hash.');
        }

        $_SESSION[SessionNames::LOGIN_HASH] = $hash;

        if(isset($_SESSION[SessionNames::IS_LOGGING_IN])) {
            unset($_SESSION[SessionNames::IS_LOGGING_IN]);
        }

        return true;
    }

    /**
     * Authenticates current user - checks if the password entered matches the one user has saved in the database.
     * 
     * @param string $password User's password
     * @return bool True if authentication is successful or false if not
     * @throws BadCredentialsException
     */
    public function authUser(string $password) {
        $rows = $this->userRepository->getUserForAuthentication($_SESSION[SessionNames::USERNAME]);

        $result = false;
        while($row = $rows->fetchAssoc()) {
            if(password_verify($password, $row['password'])) {
                $this->logger->warning('Authenticated user with username \'' . $_SESSION[SessionNames::USERNAME] . '\'.', __METHOD__);
                $result = true;
            }
        }

        if($result === false) {
            throw new BadCredentialsException(null, $_SESSION[SessionNames::USERNAME]);
        }

        return $result;
    }

    /**
     * Checks if all the necessary information about the user is saved in the session.
     * Checks if login hash in session matches the on saved in the database.
     * Checks if user is not banned or permanently banned.
     * 
     * @param string &$message Message returned
     * @return bool True if successful or false if not
     */
    public function fastAuthUser(string &$message) {
        if(isset($_SESSION[SessionNames::USER_ID]) && isset($_SESSION[SessionNames::USERNAME]) && isset($_SESSION[SessionNames::LOGIN_HASH])) {
            $dbLoginHash = $this->userRepository->getLoginHashForUserId($_SESSION[SessionNames::USER_ID]);

            if($dbLoginHash != $_SESSION[SessionNames::LOGIN_HASH]) {
                // mismatch
                $message = 'Hash in this browser does not match hash on the server.';
                return false;
            } else {
                return true;
            }
        } else {
            $message = 'Incorrectly saved session information.';
            return false;
        }
    }

    /**
     * Checks if user with passed username exists or not
     * 
     * @param string $username Username to be checked
     * @return bool True if successful or false if not
     */
    public function checkUser(string $username) {
        if($this->userRepository->getUserForAuthentication($username)->fetch() !== null) {
            return false;
        }

        return true;
    }
}

?>