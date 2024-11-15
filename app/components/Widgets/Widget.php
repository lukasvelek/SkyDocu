<?php

namespace App\Components\Widgets;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\GridBuilder2\Table;

/**
 * Common widget component
 * 
 * @author Lukas Velek
 */
class Widget extends AComponent {
    private array $data;
    private string $title;
    private bool $hasRefresh;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->data = [];
        $this->title = 'Widget';
        $this->hasRefresh = false;
    }

    /**
     * Sets widget data
     * 
     * The array should look like this:
     * ['Elements' => '1444', 'Deleted elements' => '1333'], ...
     * 
     * @param array $data Data array
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     * Sets the widget title
     * 
     * @param string $title Widget title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * Enables data refresh
     * 
     * @param bool $refresh True if refresh button is allowed or false if not
     */
    public function enableRefresh(bool $refresh = true) {
        $this->hasRefresh = $refresh;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/widget.html');
        $template->widget_title = $this->title;
        $template->data = $this->build();
        $template->controls = $this->buildControls();

        return $template->render()->getRenderedContent();
    }

    /**
     * Creates HTML code for widget controls
     * 
     * @return string HTML code
     */
    private function buildControls() {
        $code = '';
        
        return $code;
    }

    /**
     * Creates HTML code for widget content
     * 
     * @return string HTML code
     */
    private function build() {
        $rows = $this->processData();

        $table = new Table($rows);

        return $table->output()->toString();
    }

    /**
     * Processes passed data to table rows
     * 
     * @return array<Row> Rows
     */
    private function processData() {
        if(empty($this->data)) {
            $cell = new Cell();
            $cell->setContent('No data found.');
            $cell->setName('main');

            $row = new Row();
            $row->addCell($cell);
            $row->setPrimaryKey(0);

            return [$row];
        }

        $rows = [];

        $i = 0;
        foreach($this->data as $text => $value) {
            $textCell = new Cell();
            $textCell->setContent('<b>' . $text . '</b>');
            $textCell->setName($i . '-text');

            $valueCell = new Cell();
            $valueCell->setContent($value);
            $textCell->setName($i . '-value');

            $row = new Row();
            $row->addCell($textCell);
            $row->addCell($valueCell);
            $row->setPrimaryKey($i);

            $i++;

            $rows[] = $row;
        }

        return $rows;
    }

    public static function createFromComponent(AComponent $component) {}
}

?>