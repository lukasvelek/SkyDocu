<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerOrphanedFilesRemovingService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerOrphanedFilesRemovingService($app->logger, $app->serviceManager, $app->containerManager, $app->dbManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>