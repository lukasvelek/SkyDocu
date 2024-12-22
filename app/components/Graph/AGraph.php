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
    private const GRAPH_TYPE_LINE = 'line';
    private const GRAPH_TYPE_BAR = 'bar';

    protected TemplateObject $template;
    protected int $canvasWidth;
    protected int $numberOfColumns;
    protected string $title;
    private string $canvasName;
    private string $valueDescription;
    private string $graphType;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->title = 'My graph';
        $this->template = $this->loadTemplateFromPath(__DIR__ . '\\template.html');
        $this->numberOfColumns = 5;
        $this->canvasWidth = 500;
        $this->canvasName = 'myGraph';
        $this->graphType = 'line';
        $this->valueDescription = 'Value';
    }

    /**
     * Sets graph value description
     * 
     * @param string $valueDescription Value description
     */
    protected function setValueDescription(string $valueDescription) {
        $this->valueDescription = $valueDescription;
    }

    /**
     * Sets graph display mode to bars
     */
    protected function setBarGraph() {
        $this->graphType = self::GRAPH_TYPE_BAR;
    }

    /**
     * Sets graph display mode to lines
     */
    protected function setLineGraph() {
        $this->graphType = self::GRAPH_TYPE_LINE;
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
     * 
     * @param string $name Canvas name
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

                new Chart(document.getElementById("' . $this->canvasName . '"), 
                {
                    type: "' . $this->graphType . '",
                    data: {
                        labels: _data.map(row => row.date),
                        datasets: [{
                            label: "' . $this->valueDescription . '",
                            data: _data.map(row => row.queryCount)
                        }]
                    }
                });
            })();
        ');

        return implode('', $codes);
    }

    /**
     * Formats data fetched from the database to JSON format
     * 
     * @return string Data from the DB formatted to JSON
     */
    protected abstract function formatData(): string;
}

?>