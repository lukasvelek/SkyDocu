<?php

use App\Exceptions\ServiceException;
use App\Services\ProcessServiceUserHandlingService;

require_once('CommonService.php');

global $app;

try {
    $service = new ProcessServiceUserHandlingService($app);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>