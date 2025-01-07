<?php

namespace App\Modules;

use App\Exceptions\ModuleDoesNotExistException;
use Error;
use RuntimeException;

/**
 * Class that manages modules. It loads all available modules.
 * 
 * @author Lukas Velek
 */
class ModuleManager {
    /**
     * The class constructor is not used for anything currently
     */
    public function __construct() {}

    /**
     * Loads modules
     * 
     * @return array Module array
     */
    public function loadModules() {
        $modules = [];

        $folders = scandir(__DIR__);

        unset($folders[0], $folders[1]);

        foreach($folders as $folder) {
            $realPath = __DIR__ . '\\' . $folder;

            if(is_dir($realPath)) {
                $modules[] = $folder;
            }
        }

        return $modules;
    }

    /**
     * Create a single module instance
     * 
     * @param string $name Module name
     * @return AModule Module class instance that extends AModule
     */
    public function createModule(string $name) {
        if(is_dir(__DIR__ . '\\' . $name) && is_file(__DIR__ . '\\' . $name . '\\' . $name . '.php')) {
            $className = '\\App\\Modules\\' . $name . '\\' . $name;

            try {
                /** @var AModule */
                $module = new $className();
            } catch(Error $e) {
                throw new RuntimeException('An error occurred while processing request. Please try again later.', 9999, $e);
            }
            
            return $module;
        } else {
            throw new ModuleDoesNotExistException($name);
        }
    }
}

?>