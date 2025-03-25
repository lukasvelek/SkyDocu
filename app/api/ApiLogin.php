<?php

namespace App\Api;

use App\Core\Http\JsonResponse;
use App\Exceptions\ApiException;

/**
 * API login controller
 * 
 * @author Lukas Velek
 */
class ApiLogin extends ABaseApiClass {
    public function run(): JsonResponse {
        
    }

    /**
     * Returns login entered for authentication
     * 
     * @return string Login
     * @throws ApiException
     */
    private function getLogin() {
        $login = $this->getPostData()['login'];

        if($login === null) {
            throw new ApiException('No login entered for authentication.');
        }

        return $login;
    }

    /**
     * Returns password entered for authentication
     * 
     * @return string Password
     * @throws ApiException
     */
    private function getPassword() {
        $password = $this->getPostData()['password'];

        if($password === null) {
            throw new ApiException('No password entered for authentication.');
        }

        return $password;
    }

    /**
     * Returns processed POST data as an associative array
     * 
     * @return array Data
     */
    private function getPostData() {
        $data = $this->request->post('data');

        $data = json_decode($data, true);

        return $data;
    }
}

?>