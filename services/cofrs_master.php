<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerOrphanedFilesRemovingMasterService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerOrphanedFilesRemovingMasterService($app->logger, $app->serviceManager, $app->containerManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>