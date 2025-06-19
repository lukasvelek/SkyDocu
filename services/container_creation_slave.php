<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerCreationSlaveService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerCreationSlaveService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>