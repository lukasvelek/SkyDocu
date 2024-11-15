<?php

namespace App\Components\Widgets;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\UI\AComponent;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\GridBuilder2\Table;
use App\UI\HTML\HTML;

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
        $this->componentName = 'widget';
    }

    /**
     * Sets the widget internal name
     * 
     * @param string $name Widget internal name
     */
    public function setName(string $name) {
        $this->componentName = $name;
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
        $template->scripts = $this->buildJSScripts();

        return $template->render()->getRenderedContent();
    }

    /**
     * Creates HTML code for widget controls
     * 
     * @return string HTML code
     */
    private function buildControls() {
        $code = '';

        if($this->hasRefresh) {
            $code .= '<div class="row"><div class="col-md">';
            $code .= $this->createRefreshButton();
            $code .= '</div></div>';
        }
        
        return $code;
    }

    /**
     * Creates refresh button HTML code
     * 
     * @return string HTML code
     */
    private function createRefreshButton() {
        $el = HTML::el('a')
            ->href('#')
            ->text('Refresh &orarr;')
            ->onClick($this->componentName . '_refresh()')
            ->class('link')
        ;

        return $el->toString();
    }

    private function buildJSScripts() {
        $scripts = [];

        $addScript = function(AjaxRequestBuilder $arb) use (&$scripts) {
            $scripts[] = '<script type="text/javascript">' . $arb->build() . '</script>';
        };

        // REFRESH CONTROLS
        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-refresh')
            ->setFunctionName($this->componentName . '_refresh')
            ->updateHTMLElement('widget', 'widget')
        ;

        $addScript($arb);

        return implode('', $scripts);
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
        $rows = [];

        $headerRow = new Row();
        
        $titleCell = new Cell();
        $titleCell->setContent('Title');
        $titleCell->setName('title');
        $titleCell->setHeader();

        $valueCell = new Cell();
        $valueCell->setContent('Value');
        $valueCell->setName('value');
        $valueCell->setHeader();

        $headerRow->addCell($titleCell);
        $headerRow->addCell($valueCell);
        $headerRow->setPrimaryKey(0);

        $rows[] = $headerRow;

        if(empty($this->data)) {
            $cell = new Cell();
            $cell->setContent('No data found.');
            $cell->setName('no-data-found');

            $row = new Row();
            $row->addCell($cell);
            $row->setPrimaryKey(1);

            return [$row];
        }

        $i = 1;
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