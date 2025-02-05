<?php

namespace App\Exceptions;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Helpers\ExceptionHelper;
use App\Modules\TemplateObject;
use Exception;
use Throwable;

abstract class AException extends Exception {
    private string $name;
    private string $hash;
    private string $html;

    protected function __construct(string $name, string $message, ?Throwable $previous = null, bool $createFile = true) {
        $this->name = $name;
        $this->hash = HashManager::createHash(8, false);
        
        parent::__construct($message, 9999, $previous);

        if($createFile) {
            $this->saveToFile();
        }
    }

    public function getHash() {
        return $this->hash;
    }

    public function saveToFile(bool $explicit = false) {
        if(FileManager::folderExists(LOG_DIR) && ($this->getPrevious() === null || $explicit === true) && !in_array($this->name, ['ServiceException'])) {
            ExceptionHelper::saveExceptionToFile($this, $this->hash);
        }
    }
}

?>