<?php

use App\Api\Users\GetUserController;
use App\Core\Application;
use App\Exceptions\AException;

require_once('../../../../config.php');
require_once('../../../../app/app_loader.php');

try {
    $app = new Application();

    $controller = new GetUserController($app);
    echo $controller->run()->getResult();
} catch(AException $e) {
    echo $e->getMessage();
}

?>