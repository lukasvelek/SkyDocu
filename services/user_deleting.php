<?php

use App\Exceptions\ServiceException;
use App\Services\UserDeletingService;

require_once('CommonService.php');

global $app;

try {
    $service = new UserDeletingService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}