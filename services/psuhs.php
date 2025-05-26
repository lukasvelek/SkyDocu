<?php

use App\Exceptions\ServiceException;
use App\Services\ProcessServiceUserHandlingService;

require_once('CommonService.php');

global $app;

try {
    $service = new ProcessServiceUserHandlingService($app->logger, $app->serviceManager, $app->containerManager, $app->userManager, $app->dbManager, $app->userRepository);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>