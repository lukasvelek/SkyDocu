<?php

namespace App\Components\Widgets\AboutApplicationWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\UI\HTML\HTML;

/**
 * Widget with information about the application
 * 
 * @author Lukas Velek
 */
class AboutApplicationWidget extends Widget {
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->componentName = 'AboutApplicationWidget';
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Application information');
        $this->hideTitle();
    }

    /**
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
        return [
            'Application version' => '1.2-dev',
            'Application release date' => '-',
            'Project github link' => $this->getGithubLink(),
            'PHP version' => $this->getPHPVersion()
        ];
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