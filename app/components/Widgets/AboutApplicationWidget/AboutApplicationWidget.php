<?php

namespace App\Components\Widgets\AboutApplicationWidget;

use App\Components\Widgets\Widget;
use App\Core\Application;
use App\Core\Http\HttpRequest;
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
            'Application version' => APP_VERSION,
            'Version release date' => $this->getAppVersionReleaseDate()
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
            return APP_VERSION_RELEASE_DATE;
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
}

?>