<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerOrphanedFilesRemovingMasterService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerOrphanedFilesRemovingMasterService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>