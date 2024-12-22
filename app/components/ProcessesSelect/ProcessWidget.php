<?php

namespace App\Components\ProcessesSelect;

use App\Core\Http\HttpRequest;
use App\Helpers\ColorHelper;
use App\Modules\TemplateObject;
use App\UI\AComponent;

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

    private function generateColors() {
        [$fg, $bg] = ColorHelper::createColorCombination();

        $this->fgColor = $fg;
        $this->bgColor = $bg;
    }

    private function generateWidgetId() {
        return 'widget_' . $this->name;
    }

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