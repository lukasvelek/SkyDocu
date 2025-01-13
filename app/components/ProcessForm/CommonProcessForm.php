<?php

namespace App\Components\ProcessForm;

use App\Components\ProcessForm\Processes\AProcessForm;
use App\Components\ProcessForm\Processes\HomeOffice;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\AComponent;

/**
 * Frame for process form
 * 
 * @author Lukas Velek
 */
class CommonProcessForm extends AComponent {
    private string $processName;
    private TemplateObject $template;
    private array $baseUrl;

    public function __construct(HttpRequest $httpRequest) {
        parent::__construct($httpRequest);

        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\template.html');
    }

    public function startup() {
        $this->componentName = 'commonWidgetForm';
        
        parent::startup();
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    /**
     * Sets process type
     * 
     * @param string $name Process type
     */
    public function setProcess(string $name) {
        $this->processName = $name;
    }

    /**
     * Sets form base URL
     * 
     * @param array $baseUrl Base URL
     */
    public function setBaseUrl(array $baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Configures internal process form and fills the template
     */
    private function beforeRender() {
        $form = $this->prepareProcessForm();
        $form->startup();

        $this->template->form = $form->render();
    }

    /**
     * Creates an instance of process form and returns it
     * 
     * @return Processes\AProcessForm Process form instance
     */
    private function prepareProcessForm() {
        $this->baseUrl['name'] = $this->processName;

        switch($this->processName) {
            case StandaloneProcesses::HOME_OFFICE:
                $obj = HomeOffice::createFromComponent($this);
        }

        $this->injectToProcessForm($obj);

        return $obj;
    }

    /**
     * Injects default values to the process form
     * 
     * @param AProcessForm &$processForm ProcessForm instace
     */
    private function injectToProcessForm(AProcessForm &$processForm) {
        $processForm->baseUrl = $this->baseUrl;
    }
}

?>