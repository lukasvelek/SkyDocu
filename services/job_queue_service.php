<?php

use App\Exceptions\ServiceException;
use App\Services\JobQueueService;

require_once('CommonService.php');

global $app;

try {
    $service = new JobQueueService($app->logger, $app->serviceManager, $app->jobQueueManager, $app->containerManager, $app->dbManager, $app->userManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>