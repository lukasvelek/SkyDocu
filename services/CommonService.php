<?php

use App\Core\Application;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;

require_once('config.php');
require_once('app/app_loader.php');

try {
    $app = new Application();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(AException $e) {
    throw $e;
}

?>