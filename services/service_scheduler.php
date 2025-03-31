<?php

use App\Core\Application;
use App\Core\Configuration;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Helpers\BackgroundServiceScheduleHelper;

require_once('config.php');
require_once('app/app_loader.php');

const RUN_ALL_EXPLICITLY = false;
const WAIT_TIME_SECONDS = 60;

try {
    $app = new Application();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(AException $e) {
    throw $e;
}

/**
 * Here is the definition of all the code that is run every WAIT_TIME_SECONDS seconds.
 * 
 * The script get all services that should be executed as of now.
 * Then goes through every one of them and runs them in their own system process.
 * Every time it starts a process it waits 1 second for the system process startup to finish.
 */
function run() {
    global $app;

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

    say('Finished, now waiting ' . WAIT_TIME_SECONDS . ' seconds.');
    sleep(WAIT_TIME_SECONDS);
}

/**
 * Returns an array of services that should be executed just now
 * 
 * @return array<string, string> Array where key is the service title and value is the service script path
 */
function getServicesThatShouldBeExecuted(): array {
    global $app;

    $services = [];

    $qb = $app->systemServicesRepository->composeQueryForServices();
    $qb->andWhere('isEnabled = 1')
        ->andWhere('status = 1')
        ->execute();
    
    while($row = $qb->fetchAssoc()) {
        $row = DatabaseRow::createFromDbRow($row);

        if(str_contains(strtolower($row->title), 'slave') || $row->schedule === null) continue;

        $schedule = json_decode($row->schedule, true);

        $nextRun = BackgroundServiceScheduleHelper::getNextRun($schedule, $row);

        if(array_key_exists('time', $schedule['schedule'])) {
            $nextRun .= ' ' . $schedule['schedule']['time'] . ':00:00';
        } else {
            $nextRun .= ':00';
        }

        $_time = strtotime($nextRun);

        if(time() >= $_time || RUN_ALL_EXPLICITLY) {
            $services[$row->title] = $row->scriptPath;
        }
    }

    return $services;
}

/**
 * Prints out text to the console and also saves the text to a log file
 * 
 * @param string $text Output text
 * @param bool $newLine New line
 */
function say(string $text, bool $newLine = true) {
    global $app;

    $version = '[' . Configuration::getCurrentVersion() . ']';

    echo('[' . date('Y-m-d H:i:s') . '] ' . $version . ' ' . $text . ($newLine ? "\r\n" : ''));

    $app->logger->serviceInfo($text, 'service_scheduler');
}

while(true) {
    run();
}

?>