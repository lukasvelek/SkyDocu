<?php

use App\Api\Login\LoginController;
use App\Core\Application;
use App\Exceptions\AException;

require_once('../../../config.php');
require_once('../../../app/app_loader.php');

try {
    $app = new Application();

    $loginController = new LoginController($app);
    echo $loginController->run()->getResult();
} catch(AException $e) {
    echo $e->getMessage();
}

?>