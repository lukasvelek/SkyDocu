<?php

namespace App\Services;

use App\Core\FileManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use Exception;

class LogRotateService extends AService {
    public function __construct(Logger $logger, ServiceManager $serviceManager) {
        parent::__construct('LogRotate', $logger, $serviceManager);
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop(true);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $oldFiles = $this->getAllLogFiles();

        $this->logInfo('Found ' . count($oldFiles) . ' old log files.');

        [$ok, $error] = $this->moveFiles($oldFiles);

        $this->logInfo('Moved ' . count($ok) . ' old log files to their new destination.');
        $this->loginfo('Could not move ' . count($error) . ' old log files to their new destination.');
    }

    private function moveFiles(array $oldFiles) {
        $this->logInfo('Starting to move files.');
        $ok = [];
        $error = [];
        foreach($oldFiles as $filename => $filepath) {
            $this->logInfo('Moving file \'' . $filename . '\' located in \'' . $filepath . '\'.');

            $filenameParts = explode('.', $filename);
            
            $logDate = explode('_', $filenameParts[0])[1];

            $logYear = explode('-', $logDate)[0];
            $logMonth = explode('-', $logDate)[1];
            $logDay = explode('-', $logDate)[2];

            if(!FileManager::folderExists(APP_ABSOLUTE_DIR . LOG_DIR . $logYear)) {
                $this->logInfo('Folder \'' . APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\' does not exist. Creating...');

                FileManager::createFolder(APP_ABSOLUTE_DIR . LOG_DIR . $logYear);
            }
            if(!FileManager::folderExists(APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth)) {
                $this->logInfo('Folder \'' . APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth . '\' does not exist. Creating...');

                FileManager::createFolder(APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth);
            }
            if(!FileManager::folderExists(APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth . '\\' . $logDay)) {
                $this->logInfo('Folder \'' . APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth . '\\' . $logDay . '\' does not exist. Creating...');

                FileManager::createFolder(APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth . '\\' . $logDay);
            }

            $newPath = APP_ABSOLUTE_DIR . LOG_DIR . $logYear . '\\' . $logMonth . '\\' . $logDay . '\\' . $filename;
            $this->logInfo('New path for file \'' . $filename . '\' is \'' . $newPath . '\'.');

            $result = FileManager::moveFile($filepath, $newPath);

            if($result === true) {
                $this->logInfo('Successfully moved file.');
                
                $ok[] = $newPath;
            } else {
                $this->logInfo('Could not move file');

                $error[] = $filepath;
            }
        }

        return [$ok, $error];
    }

    private function getAllLogFiles() {
        $files = FileManager::getFilesInFolder(APP_ABSOLUTE_DIR . LOG_DIR, false);

        $oldFiles = [];
        foreach($files as $filename => $filepath) {
            if($filename == '__filesDeleted.log') {
                continue;
            }

            $filenameParts = explode('.', $filename);
            
            $logDate = explode('_', $filenameParts[0])[1];

            if($logDate != date('Y-m-d')) {
                $oldFiles[$filename] = $filepath;
            }
        }

        return $oldFiles;
    }
}

?>