<?php

namespace App\Core;

use App\Exceptions\AException;
use App\Exceptions\RouterException;
use App\Modules\AModule;
use App\Modules\APresenter;
use App\Modules\ModuleManager;
use Exception;

/**
 * Router helps with the URL generation and routing between different application pages
 * 
 * @author Lukas Velek
 */
class Router {
    private ?APresenter $presenter;
    private ?ModuleManager $moduleManager;

    public function __construct() {
        $this->presenter = null;
        $this->moduleManager = null;
    }

    /**
     * Injects required variables
     * 
     * @param APresenter $presenter Calling presenter instance
     * @param ModuleManager $moduleManager ModuleManager instance
     */
    public function inject(
        APresenter $presenter,
        ModuleManager $moduleManager
    ) {
        $this->presenter = $presenter;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Generates stringified URL from URL parameter array
     * 
     * @param array $parts URL parameter array
     * @return string Stringified URL
     */
    public static function generateUrl(array $parts) {
        $queryParameters = [];

        foreach($parts as $k => $v) {
            if(is_numeric($k)) {
                $queryParameters[] = $v;
            } else {
                $queryParameters[] = $k . '=' . $v;
            }
        }
        
        return '?' . implode('&', $queryParameters);
    }

    /**
     * Checks if given endpoint (internal URL address) exists
     * 
     * @param array $parts URL parameter array
     * @param bool $throwException True if exception should be thrown or false if a boolean should be returned
     * @return bool True if exists or false if not
     */
    public function checkEndpointExists(array $parts, bool $throwException = false) {
        $moduleName = self::getModuleNameFromArrayUrl($parts);
        $presenterName = self::getPresenterNameFromArrayUrl($parts);
        $actionName = self::getActionNameFromArrayUrl($parts);

        try {
            if($moduleName === null || $presenterName === null || $actionName === null) {
                throw new RouterException('No parameters entered.', null);
            }

            /**
             * @var AModule $module
             */
            $module = $this->getModuleInstance($moduleName);
            $module->loadPresenters();
            if(!$module->checkPresenterExists($presenterName)) {
                throw new RouterException(sprintf('Presenter \'%s\' does not exist.', $presenterName));
            }

            $presenter = $this->getPresenterInstance($presenterName, $module);

            $handleActionName = 'handle' . ucfirst($actionName);
            $renderActionName = 'render' . ucfirst($actionName);

            if(!method_exists($presenter, $handleActionName) && !method_exists($presenter, $renderActionName)) {
                throw new RouterException(sprintf('Page \'%s:%s\' does not exist.', $presenterName, $actionName));
            }
        } catch(AException|Exception $e) {
            if($throwException) {
                throw $e;
            }
            return false;
        }

        return true;
    }

    /**
     * Returns an instance of a presenter
     * 
     * @param string $presenterName Presenter name
     * @param AModule $module Module instance
     * @return APresenter Presenter instance
     */
    private function getPresenterInstance(string $presenterName, AModule $module) {
        if(!str_ends_with($presenterName, 'Presenter')) {
            $presenterName .= 'Presenter';
        }
        return $module->createPresenterInstance($presenterName);
    }

    /**
     * Returns an instance of a module
     * 
     * @param string $moduleName Module name
     * @return AModule Module instance
     */
    private function getModuleInstance(string $moduleName) {
        if(!str_ends_with($moduleName, 'Module')) {
            $moduleName .= 'Module';
        }
        return $this->moduleManager->createModule($moduleName);
    }

    /**
     * Returns module name from URL parameter array
     * 
     * @param array $parts URL parameter array
     * @return ?string Module name or null
     */
    public static function getModuleNameFromArrayUrl(array $parts) {
        if(array_key_exists('page', $parts)) {
            $page = $parts['page'];
            return explode(':', $page)[0];
        } else {
            return null;
        }
    }

    /**
     * Returns presenter name from URL parameter array
     * 
     * @param array $parts URL parameter array
     * @return ?string Presenter name or null
     */
    public static function getPresenterNameFromArrayUrl(array $parts) {
        if(array_key_exists('page', $parts)) {
            $page = $parts['page'];
            return explode(':', $page)[1];
        } else {
            return null;
        }
    }

    /**
     * Returns action name from URL parameter array
     * 
     * @param array $parts URL parameter array
     * @return ?string Action name or null
     */
    public static function getActionNameFromArrayUrl(array $parts) {
        if(array_key_exists('action', $parts)) {
            return $parts['action'];
        } else {
            return null;
        }
    }
}

?>