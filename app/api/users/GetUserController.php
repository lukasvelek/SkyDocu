<?php

namespace App\Api\Users;

use App\Api\ABaseApiClass;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\ApiException;

/**
 * GetUser controller
 * 
 * @author Lukas Velek
 */
class GetUserController extends ABaseApiClass {
    public function run(): JsonResponse {
        try {
            $this->startup();

            $this->tokenAuth();

            $userId = $this->getUserId();

            $user = $this->app->userManager->getUserRowById($userId);

            $properties = $this->getProperties();

            $results = [];
            foreach($properties as $property) {
                if(in_array($property, ['password', 'loginHash'])) {
                    continue;
                }

                $results[$property] = $user->$property;
            }

            return new JsonResponse(['data' => $results]);
        } catch(AException $e) {
            return $this->convertExceptionToJson($e);
        }
    }

    /**
     * Returns user ID
     */
    private function getUserId(): string {
        $userId = $this->get('userId');

        if($userId === null) {
            throw new ApiException('No user ID entered.');
        }

        return $userId;
    }

    /**
     * Returns properties
     */
    private function getProperties(): array {
        $properties = $this->get('properties');

        if($properties === null || empty($properties)) {
            throw new ApiException('No properties entered.');
        }

        return $properties;
    }
}

?>