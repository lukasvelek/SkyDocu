<?php

use App\Exceptions\ServiceException;
use App\Services\ProcessSubstituteService;

require_once('CommonService.php');

global $app;

try {
    $service = new ProcessSubstituteService($app->logger, $app->serviceManager, $app->userAbsenceManager, $app->userSubstituteManager, $app->containerManager, $app->dbManager, $app->entityManager, $app->userManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>