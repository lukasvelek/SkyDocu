<?php

namespace App\Components\ProcessesSelect;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Managers\Container\StandaloneProcessManager;
use App\Modules\TemplateObject;
use App\UI\AComponent;

/**
 * ProcessesSelect is a component that displays enabled standalone processes to the user
 * 
 * @author Lukas Velek
 */
class ProcessesSelect extends AComponent {
    private TemplateObject $template;
    private array $widgets;
    private StandaloneProcessManager $standaloneProcessManager;

    public function __construct(HttpRequest $request, StandaloneProcessManager $standaloneProcessManager) {
        parent::__construct($request);

        $this->standaloneProcessManager = $standaloneProcessManager;

        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\template.html');
        $this->widgets = [];
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Loads template and fills it
     */
    private function beforeRender() {
        $this->loadWidgets();
        $this->fillTemplate();
    }

    /**
     * Loads widgets - enabled processes
     */
    private function loadWidgets() {
        $enabledWidgets = $this->standaloneProcessManager->getEnabledProcessTypes();

        foreach($enabledWidgets as $row) {
            $key = $row->typeKey;
            $title = StandaloneProcesses::toString($key);

            $params = [
                'name' => $key
            ];

            $link = $this->createFullURLString($this->httpRequest->get('page'), 'processForm', $params);

            $widget = new ProcessWidget($this->httpRequest, $key, $title, $link);
            $widget->startup();
            $this->widgets[] = $widget;
        }
    }

    /**
     * Fills the template with data
     */
    private function fillTemplate() {
        $countInRow = 3;

        $widgetCount = count($this->widgets);

        if($widgetCount > 0) {
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
        } else {
            $code = ['No processes found.'];
        }

        $this->template->widgets = implode('', $code);
    }
}

?>