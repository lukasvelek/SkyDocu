<?php

use App\Api\ApiLogin;
use App\Core\Application;
use App\Exceptions\AException;

require_once('../../../config.php');
require_once('../../../app/app_loader.php');

try {
    $app = new Application();

    $apiLogin = new ApiLogin($app);
    echo $apiLogin->run()->getResult();
} catch(AException $e) {
    echo $e->getMessage();
}

?>