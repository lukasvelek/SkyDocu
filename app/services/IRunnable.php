<?php

namespace App\Services;

/**
 * IRunnable interface defines that its implementations can be run
 * 
 * @author Lukas Velek
 */
interface IRunnable {
    /**
     * The default method called when a background service is executed
     */
    function run();
}

?>