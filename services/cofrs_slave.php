<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerOrphanedFilesRemovingSlaveService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerOrphanedFilesRemovingSlaveService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>