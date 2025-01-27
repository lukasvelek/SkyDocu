<?php

namespace App\Components\ProcessesSelect;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\AComponent;

/**
 * Single process widget
 * 
 * @author Lukas Velek
 */
class ProcessWidget extends AComponent {
    private string $title;
    private string $bgColor;
    private string $fgColor;
    private string $link;
    private string $name;

    private TemplateObject $template;

    public function __construct(HttpRequest $request, string $name, string $title, string $link) {
        parent::__construct($request);

        $this->title = $title;
        $this->link = $link;
        $this->name = $name;

        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\widget.html');
    }

    public function startup() {
        parent::startup();
        
        $this->componentName = $this->generateWidgetId();
    }

    /**
     * Generates colors for the widget
     */
    private function generateColors() {
        $this->fgColor = StandaloneProcesses::getColor($this->name);
        $this->bgColor = StandaloneProcesses::getBackgroundColor($this->name);
    }

    /**
     * Generates widget ID
     * 
     * @return string Widget ID
     */
    private function generateWidgetId() {
        return 'widget_' . $this->name;
    }

    /**
     * Fills the template
     */
    private function beforeRender() {
        $this->generateColors();

        $this->template->title = $this->title;
        $this->template->color = $this->fgColor;
        $this->template->background = $this->bgColor;
        $this->template->link = $this->link;
        $this->template->widget_id = $this->componentName;
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}
}

?>