<?php

namespace App\Components\ProcessReportsSelect;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Managers\Container\StandaloneProcessManager;
use App\Modules\TemplateObject;
use App\UI\AComponent;

class ProcessReportsSelect extends AComponent {
    private TemplateObject $template;
    private array $widgets;
    private StandaloneProcessManager $spm;

    public function __construct(HttpRequest $request, StandaloneProcessManager $spm) {
        parent::__construct($request);

        $this->spm = $spm;

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
        $enabledProcessTypes = $this->spm->getEnabledProcessTypes();

        foreach($enabledProcessTypes as $row) {
            $key = $row->typeKey;
            
            switch($key) {
                case StandaloneProcesses::HOME_OFFICE:
                    $params = [
                        'name' => $key,
                        'view' => 'my'
                    ];

                    $link = $this->createFullURLString($this->httpRequest->query['page'], 'showReport', $params);

                    $widget = new ReportWidget($this->httpRequest, $key, 'My Home office requests', $link);
                    $widget->startup();
                    $this->widgets[] = $widget;
                    
                    break;
            }
        }
    }

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