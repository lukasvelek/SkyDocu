<?php

/**
 * This script is made for issue #164 (https://github.com/lukasvelek/SkyDocu/issues/164).
 * 
 * Before that issue, each container had its own transaction log. The mentioned issue came with a proposal, that only a master or global transaction log should exist.
 * 
 * Therefore this script is responsible for migrating all container transaction logs to the master transaction log.
 * 
 * List of steps the script performs:
 * 1. Get all created containers
 * 2. For each of the container, get the list of their transaction log entries
 * 3. Move all the container transaction log entries to the master transaction log
 * 4. Remove all container transaction log entries
 */

use App\Core\Application;
use App\Core\Configuration;
use App\Core\DatabaseConnection;
use App\Core\GUID;
use App\Repositories\ContentRepository;
use QueryBuilder\QueryBuilder;

require_once('../config.php');
require_once('../version.php');
require_once('../app/app_loader.php');

const LOG = true;

$app = new Application();

$containers = $app->containerManager->getAllContainers(true, true);

say(sprintf('Found %d containers to process.', count($containers)));

$i = 1;
foreach($containers as  $container) {
    /**
     * @var \App\Entities\ContainerEntity $container
     */
    
    say(sprintf('Starting to process container %d/%d with ID "%s".', $i, count($containers), $container->getId()));

    $conn = $app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

    say('Successfully retrieved database connection to container database.');

    $qb = getQb($conn);

    $qb->select(['*'])
        ->from('transaction_log')
        ->execute();

    $dataToInsert = [];
    while($row = $qb->fetchAssoc()) {
        $userId = $row['userId'];
        $callingMethod = $row['callingMethod'];
        $dateCreated = $row['dateCreated'];

        $dataToInsert[$container->getId()] = [
            'userId' => $userId,
            'callingMethod' => $callingMethod,
            'dateCreated' => $dateCreated,
            'containerId' => $container->getId()
        ];
    }

    say(sprintf('Found %d container transaction log entries to process.', count($dataToInsert)));

    foreach($dataToInsert as $containerId => $data) {
        $transactionId = GUID::generate();

        $userId = $data['userId'];
        $callingMethod = $data['callingMethod'];
        $dateCreated = $data['dateCreated'];

        $sql = '';

        $app->transactionLogRepository->createNewEntry($transactionId, $userId, $callingMethod, $sql, $containerId, $dateCreated);
    }

    say('Successfully moved all container transaction log entries.');

    $qb = getQb($conn);

    $qb->delete()
        ->from('transaction_log')
        ->execute();

    say('Successfully deleted all container transaction log entries.');

    $i++;
}

/**
 * Returns a new QueryBuilder instance
 * 
 * @param DatabaseConnection $conn Database connection instance
 */
function getQb(DatabaseConnection $conn) {
    global $app;

    return new QueryBuilder($conn, $app->logger);
}

/**
 * Logs an information
 */
function say(string $text) {
    if(LOG) {
        // [date] [app version] text
        $message = sprintf('[%s] [%s] %s', date('Y-m-d H:i:s'), Configuration::getCurrentVersion(), $text);
        echo $message . "\r\n";
    }
}

?>