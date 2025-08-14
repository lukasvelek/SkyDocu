<?php

namespace App\Core\DB;

use App\Core\HashManager;

/**
 * Common class for all database migrations for containers
 * 
 * @author Lukas Velek
 */
abstract class AContainerBaseMigration extends ABaseMigration {
    protected string $containerId;

    /**
     * Sets container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }
}

?>