<?php

namespace App\Components\Graph;

use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\AComponent;

/**
 * Common class for graphs
 * 
 * @author Lukas Velek
 */
abstract class AGraph extends AComponent {
    protected TemplateObject $template;
    protected int $canvasWidth;
    protected int $numberOfColumns;
    protected string $title;
    private string $canvasName;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->title = 'My graph';
        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\template.html');
        $this->numberOfColumns = 5;
        $this->canvasWidth = 500;
        $this->canvasName = 'myGraph';
    }

    /**
     * Sets the canvas width
     * 
     * @param int $canvasWidth Canvas width
     */
    public function setCanvasWidth(int $canvasWidth) {
        $this->canvasWidth = $canvasWidth;
    }

    /**
     * Sets the number of columns displayed
     * 
     * @param int $columns Number of columns
     */
    public function setNumberOfColumns(int $columns) {
        $this->numberOfColumns = $columns;
    }

    /**
     * Sets canvas name
     */
    protected function setCanvasName(string $name) {
        $this->canvasName = 'canvas_' . $name;
    }

    /**
     * Fills the template with data
     */
    protected function beforeRender() {
        $this->template->title = $this->title;
        $this->template->scripts = $this->createJSScripts();
        $this->template->canvas_width = $this->canvasWidth;
        $this->template->canvas_name = $this->canvasName;
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    /**
     * Creates JS scripts
     * 
     * @return string JS script
     */
    protected function createJSScripts() {
        $codes = [];

        $addScript = function(string $code) use (&$codes) {
            $codes[] = '<script type="text/javascript">' . $code . '</script>';
        };

        $addScript('
            (async function() {
                const _data = [' . $this->formatData() . '];

                new Chart(document.getElementById("canvas_containerUsageAverageResponseTime"), 
                {
                    type: "line",
                    data: {
                        labels: _data.map(row => row.date),
                        datasets: [{
                            label: "[ms] Response time",
                            data: _data.map(row => row.queryCount)
                        }]
                    }
                });
            })();
        ');

        return implode('', $codes);
    }

    protected abstract function formatData(): string;
}

?>