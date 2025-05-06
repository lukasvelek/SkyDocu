<?php

namespace App\Entities;

use App\Exceptions\GeneralException;

/**
 * Defines a single API token
 * 
 * @author Lukas Velek
 */
class ApiTokenEntity {
    private const _TOKEN = 'token';
    private const _CONTAINER_ID = 'containerId';
    private const _ENTITY_ID = 'entityId';
    private const _USER_ID = 'userId';

    private string $token;
    private string $containerId;
    private string $entityId;
    private ?string $userId = null;

    /**
     * Class constructor
     * 
     * @param array $token Token
     */
    public function __construct(array $token) {
        $this->checkArrayKeys($token);

        $this->token = $token[self::_TOKEN];
        $this->containerId = $token[self::_CONTAINER_ID];
        $this->entityId = $token[self::_ENTITY_ID];

        if(array_key_exists(self::_USER_ID, $token)) {
            $this->userId = $token[self::_USER_ID];
        }
    }

    /**
     * Sets user ID
     */
    public function setUserId(string $userId) {
        $this->userId = $userId;
    }

    /**
     * Returns user ID
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Returns container ID
     */
    public function getContainerId(): string {
        return $this->containerId;
    }

    /**
     * Returns entity ID
     */
    public function getEntityId(): string {
        return $this->entityId;
    }

    /**
     * Returns access token
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * Converts the entity to transmittable string
     */
    public function convertToToken(): string {
        $result = [
            self::_TOKEN => $this->token,
            self::_CONTAINER_ID => $this->containerId,
            self::_ENTITY_ID => $this->entityId
        ];

        if($this->userId !== null) {
            $result[self::_USER_ID] = $this->userId;
        }

        return base64_encode(serialize($result));
    }

    /**
     * Creates a new ApiTokenEntity from transmitted token
     * 
     * @param string $token Transmitted token
     */
    public static function convertFromToken(string $token): ApiTokenEntity {
        $decodedToken = unserialize(base64_decode($token));

        $entity = new ApiTokenEntity($decodedToken);

        return $entity;
    }

    /**
     * Creates a new entity
     * 
     * @param string $token Generated token
     * @param string $containerId Container ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     */
    public static function createNewEntity(string $token, string $containerId, string $entityId): ApiTokenEntity {
        $array = [
            self::_TOKEN => $token,
            self::_CONTAINER_ID => $containerId,
            self::_ENTITY_ID => $entityId
        ];
        
        return new ApiTokenEntity($array);
    }

    /**
     * Checks if mandatory keys exist in given array
     * 
     * @param array $token Token
     * @throws GeneralException
     */
    private function checkArrayKeys(array $token) {
        $mandatoryKeys = [
            self::_TOKEN,
            self::_CONTAINER_ID,
            self::_ENTITY_ID
        ];

        foreach($mandatoryKeys as $key) {
            if(!array_key_exists($key, $token)) {
                throw new GeneralException('Parameter \'' . $key . '\' is not defined in the token.');
            }
        }
    }
}

?>