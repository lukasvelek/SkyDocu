<?php

namespace App\Components\Widgets\AboutApplicationWidget;

use App\Components\Widgets\Widget;
use App\Core\Configuration;
use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Helpers\DateTimeFormatHelper;
use App\UI\HTML\HTML;

/**
 * Widget with information about the application
 * 
 * @author Lukas Velek
 */
class AboutApplicationWidget extends Widget {
    private bool $disableGithubLink;
    private bool $disablePHPVersion;
    private ?string $containerId;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->componentName = 'AboutApplicationWidget';

        $this->disableGithubLink = false;
        $this->disablePHPVersion = false;
        $this->containerId = null;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Application information');
        $this->hideTitle();
    }

    /**
     * Disables Github link
     */
    public function disableGithubLink() {
        $this->disableGithubLink = true;
    }

    /**
     * Enables Github link
     */
    public function enableGithubLink() {
        $this->disableGithubLink = false;
    }

    /**
     * Disables PHP version
     */
    public function disablePHPVersion() {
        $this->disablePHPVersion = true;
    }

    /**
     * Enables PHP version
     */
    public function enablePHPVersion() {
        $this->disablePHPVersion = false;
    }

    /**
     * Sets container view
     * 
     * @param string $containerId Container ID
     */
    public function setContainerView(string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
        if($this->containerId === null) {
            return $this->processDataForGeneralView();
        } else {
            return $this->processDataForContainerView();
        }
    }

    /**
     * Processes widget data for general view
     * 
     * @return array Widget rows
     */
    private function processDataForGeneralView(): array {
        $data = [
            'Application version' => Configuration::getCurrentVersion(),
            'Version release date' => $this->getAppVersionReleaseDate(),
            'Application database schema' => $this->getAppDbSchema()
        ];

        if(!$this->disableGithubLink) {
            $data['Project github link'] = $this->getGithubLink();
        }
        if(!$this->disablePHPVersion) {
            $data['PHP version'] = $this->getPHPVersion();
        }

        return $data;
    }

    /**
     * Processes widget data for container view
     * 
     * @return array Widget rows
     */
    private function processDataForContainerView(): array {
        $data = [
            'Application version' => Configuration::getCurrentVersion(),
            'Version release date' => $this->getAppVersionReleaseDate(),
            'Container database schema' => $this->getContainerDbSchema()
        ];

        return $data;
    }

    /**
     * Returns container database schema
     * 
     * @return string Container database schema
     */
    private function getContainerDbSchema(): string {
        $container = $this->app->containerManager->getContainerById($this->containerId, true);

        $version = $container->getDefaultDatabase()->getDbSchema();

        $files = FileManager::getFilesInFolder(APP_ABSOLUTE_DIR . 'data\\db\\migrations\\containers');

        $date = '';
        foreach($files as $fileR => $fileA) {
            $d = explode('_', $fileR)[1];
            $v = explode('_', $fileR)[2];

            if($v == $version) {
                $date = $d;
            }
        }

        return sprintf('%d (<span title="%s">%s</span>)', $version, $date, DateTimeFormatHelper::formatDateToUserFriendly($d, $this->app->currentUser->getDateFormat()));
    }

    /**
     * Returns application version release date
     * 
     * @return string Link
     */
    private function getAppVersionReleaseDate(): string {
        if(APP_VERSION_RELEASE_DATE == '-') {
            // not released yet
            return '<span title="This version has not been released yet.">' . APP_VERSION_RELEASE_DATE . '</span>';
        } else {
            return DateTimeFormatHelper::formatDateToUserFriendly(APP_VERSION_RELEASE_DATE, $this->app->currentUser->getDateFormat());
        }
    }

    /**
     * Returns project's github link
     * 
     * @return string Link
     */
    private function getGithubLink() {
        $el = HTML::el('a');

        $el->class('grid-link')
            ->href('https://github.com/lukasvelek/SkyDocu/')
            ->text('SkyDocu GitHub link');

        return $el->toString();
    }

    /**
     * Returns PHP version
     * 
     * @return string PHP version
     */
    private function getPHPVersion() {
        return phpversion();
    }

    /**
     * Returns the application database schema
     * 
     * @return string Application database schema
     */
    private function getAppDbSchema(): string {
        $migration = FileManager::loadFile(APP_ABSOLUTE_DIR . 'app\\core\\migration');
        $migrationParts = explode('_', $migration);
        $schema = $migrationParts[2];
        $date = $migrationParts[1];

        $date = '<span title="' . $date . '">' . DateTimeFormatHelper::formatDateToUserFriendly($date, $this->app->currentUser->getDateFormat()) . '</span>';

        return (int)$schema . ' (' . $date . ')';
    }
}

?>