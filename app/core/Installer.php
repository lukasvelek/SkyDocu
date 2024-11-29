<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\Exceptions\InstallationException;

/**
 * Installer is responsible for installing the application
 * 
 * @author Lukas Velek
 */
class Installer {
    private DatabaseConnection $db;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     * @param DatabaseConnection $db DatabaseConnection instance
     */
    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
    }

    /**
     * Installs the application
     * 
     * 1. Installs the database
     * 2. Encrypts the config file (only sensitive information)
     * 3. Creates "install" file
     */
    public function install() {
        try {
            $this->db->beginTransaction();

            $this->installDb();
            /*if(!$this->encryptConfigFile()) {
                throw new InstallationException('Could not encrypt the configuration file.');
            }*/
            if(!$this->createInstallFile()) {
                throw new InstallationException('Could not create the installation file.');
            }

            $this->db->commit();
        } catch(AException $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Creates "install" file
     * 
     * @return bool True on success or false on failure
     */
    private function createInstallFile() {
        $date = new DateTime();
        
        $result = FileManager::saveFile(APP_ABSOLUTE_DIR . 'app\\core\\', 'install', 'installed - ' . $date);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Install the database
     */
    private function installDb() {
        $this->db->installDb();
    }
}

?>