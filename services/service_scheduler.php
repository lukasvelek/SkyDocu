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

function run() {
    $waitTime = 60; // for testing 60s, for production 1h

    echo('Finished, now waiting ' . $waitTime . ' seconds.');
    sleep($waitTime);
}

function getServicesThatShouldBeExecuted() {
    global $app;

    $qb = $app->systemServicesRepository->composeQueryForServices();
    $qb->andWhere('isEnabled = 1')
        ->andWhere('status = 1')
        ->execute();
    
    while($row = $qb->fetchAssoc()) {
        $schedule = json_decode($row['schedule'], true);
    }
}

run();

?>