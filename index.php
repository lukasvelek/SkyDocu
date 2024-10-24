<?php

use App\Core\Application;
use App\Exceptions\ApplicationInitializationException;

session_start();

require_once('config.php');

try {
    require_once('app/app_loader.php');
} catch(Exception $e) {
    echo($e->getMessage());
    exit;
}

try {
    $app = new Application();

    if($app === null) {
        throw new ApplicationInitializationException('Could not instantialize application.');
    }
} catch(Exception $e) {
    echo($e->getMessage());
    exit;
}

if(!isset($_GET['page'])) {
    // default redirect address
    $app->redirect(['page' => 'Anonym:AutoLogin', 'action' => 'checkLogin']);
}

try {
    $app->run();
} catch(Exception $e) {
    echo $e->getMessage();
    exit;
}

?>