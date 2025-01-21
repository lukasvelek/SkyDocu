<?php

namespace App\Components\ProcessReportsSelect;

use App\Core\Http\HttpRequest;
use App\Managers\Container\StandaloneProcessManager;
use App\Modules\TemplateObject;
use App\UI\AComponent;

/**
 * ProcessReportsSelect is a component that displays enabled process reports to the user
 * 
 * @author Lukas Velek
 */
class ProcessReportsSelect extends AComponent {
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
     * Fills the template
     */
    private function beforeRender() {
        $this->loadWidgets();
        $this->fillTemplate();
    }

    /**
     * Loads widgets
     */
    private function loadWidgets() {
        $enabledProcessTypes = $this->standaloneProcessManager->getEnabledProcessTypes();

        foreach($enabledProcessTypes as $row) {
            $key = $row->typeKey;

            $params = [
                'name' => $key,
                'view' => 'my'
            ];

            $link = $this->createFullURLString($this->httpRequest->query('page'), 'showReport', $params);

            $widget = new ReportWidget($this->httpRequest, $key, 'My ' . $row->title . ' requests', $link);
            $widget->startup();
            $this->widgets[] = $widget;

            $params['view'] = 'all';

            $link = $this->createFullURLString($this->httpRequest->query('page'), 'showReport', $params);

            $widget = new ReportWidget($this->httpRequest, $key, 'All ' . $row->title . ' requests', $link);
            $widget->startup();
            $this->widgets[] = $widget;
        }
    }

    /**
     * Fills the template
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