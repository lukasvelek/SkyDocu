<?php

namespace App\Components\Widgets;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\GridBuilder2\Table;

class Widget extends AComponent {
    private array $data;
    private string $title;
    private bool $hasRefresh;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->data = [];
        $this->title = 'Widget';
        $this->hasRefresh = false;
    }

    public function setData(array $data) {
        $this->data = $data;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function enableRefresh(bool $refresh) {
        $this->hasRefresh = $refresh;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/widget.html');
        $template->widget_title = $this->title;
        $template->data = $this->build();
        $template->controls = $this->buildControls();

        return $template->render()->getRenderedContent();
    }

    private function buildControls() {
        $code = '';

        
        
        return $code;
    }

    private function build() {
        $rows = $this->processData();

        $table = new Table($rows);

        return $table->output()->toString();
    }

    private function processData() {
        $rows = [];

        $i = 0;
        foreach($this->data as $text => $value) {
            $textCell = new Cell();
            $textCell->setContent('<b>' . $text . '</b>');

            $valueCell = new Cell();
            $valueCell->setContent($value);

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