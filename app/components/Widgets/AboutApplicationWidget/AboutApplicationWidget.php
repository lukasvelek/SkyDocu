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

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->componentName = 'AboutApplicationWidget';

        $this->disableGithubLink = false;
        $this->disablePHPVersion = false;
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
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
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