<?php

use App\Core\Application;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;

require_once('config.php');
require_once('app/app_loader.php');

const RUN_ALL_EXPLICITLY = true;

try {
    $app = new Application();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(AException $e) {
    throw $e;
}

function run() {
    global $app;

    $waitTime = 60; // for testing 60s, for production 1h

    $services = getServicesThatShouldBeExecuted();

    if(count($services) > 0) {
        say(sprintf('Found %d services that are scheduled. Starting...', count($services)));

        foreach($services as $title => $scriptPath) {
            say(sprintf('Starting \'' . $title . '\'...'));
            $result = $app->serviceManager->runService($scriptPath);
            if($result === true) {
                say(sprintf('Service \'' . $title . '\' started.'));
            } else {
                say(sprintf('Service \'' . $title . '\' could not be started.'));
            }
            sleep(1);
        }
    } else {
        say(sprintf('Found %d services that are scheduled.', count($services)));
    }

    say('Finished, now waiting ' . $waitTime . ' seconds.');
    sleep($waitTime);
    run();
}

function getServicesThatShouldBeExecuted() {
    global $app;

    $services = [];

    $qb = $app->systemServicesRepository->composeQueryForServices();
    $qb->andWhere('isEnabled = 1')
        ->andWhere('status = 1')
        ->execute();
    
    while($row = $qb->fetchAssoc()) {
        $schedule = json_decode($row['schedule'], true);

        $days = $schedule['schedule']['days'];
        $time = $schedule['schedule']['time'];

        $todayShortcut = date('D');

        if(RUN_ALL_EXPLICITLY ||
           (in_array($todayShortcut, explode(';', $days)) &&
           (int)date('H') >= (int)$time)
        ) {
            $services[$row['title']] = $row['scriptPath'];
        }
    }

    return $services;
}

function say(string $text, bool $newLine = true) {
    global $app;

    echo($text . ($newLine ? "\r\n" : ''));

    $app->logger->serviceInfo($text, 'service_scheduler');
}

run();

?>