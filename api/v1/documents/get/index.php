<?php

use App\Api\Documents\GetDocumentsController;
use App\Core\Application;
use App\Exceptions\AException;

require_once('../../../../config.php');
require_once('../../../../app/app_loader.php');
require_once('../../common.php');

try {
    $app = new Application();

    $controller = new GetDocumentsController($app);

    echo $controller->getResult();
} catch(AException $e) {
    echo convertExceptionToJson($e);
}

?>