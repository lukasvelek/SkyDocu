<?php

use App\Exceptions\ServiceException;
use App\Services\LogRotateService;

require_once('CommonService.php');

global $app;

try {
    $service = new LogRotateService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>