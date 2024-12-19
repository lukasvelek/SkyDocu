<?php

namespace App\Components\ProcessesSelect;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Managers\Container\ProcessManager;
use App\Modules\TemplateObject;
use App\UI\AComponent;

class ProcessesSelect extends AComponent {
    private TemplateObject $template;
    private array $widgets;

    public function __construct(HttpRequest $request, ProcessManager $processManager) {
        parent::__construct($request);

        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\template.html');

        $this->widgets = [];
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

    private function beforeRender() {
        $this->loadWidgets();
        $this->fillTemplate();
    }

    private function loadWidgets() {
        $widgets = StandaloneProcesses::getAll();

        foreach($widgets as $key => $title) {
            $params = [
                'name' => $key
            ];

            $link = $this->createFullURLString($this->httpRequest->query['page'], 'processForm', $params);

            $widget = new ProcessWidget($this->httpRequest, $key, $title, $link);
            $widget->startup();
            $this->widgets[] = $widget;
        }
    }

    private function fillTemplate() {
        $countInRow = 3;

        $widgetCount = count($this->widgets);

        $rows = ceil($widgetCount / $countInRow);

        $code = [];
        $addRow = function(string $line) use (&$code) {
            $code[] = $line;
        };
        
        $w = 0;
        for($i = 0; $i < $rows; $i++) {
            $addRow('<div class="row">');

            for($j = 0; $j < $countInRow; $j++) {
                $addRow('<div class="col-md">');

                if($widgetCount > $w) {
                    $widget = $this->widgets[$w];

                    $addRow($widget->render());
                }

                $addRow('</div>');

                $w++;
            }

            $addRow('</div>');

            if(($i + 1) < $rows) {
                $addRow('<br>');
            }
        }

        $this->template->widgets = implode('', $code);
    }
}

?>