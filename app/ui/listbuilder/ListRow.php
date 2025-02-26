<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;

class ListRow extends AListElement {
    public HTML $html;
    public int $index;
    public ArrayRow $rowData;
    
    /**
     * @var array<ListCell> $cells
     */
    private array $cells;

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();

        $this->cells = [];
        $this->html = HTML::el('tr');
        $this->index = 0;
    }

    /**
     * Adds cell
     * 
     * @param ListCell $cell Cell instance
     * @param bool $prepend True if the cell should be prepended
     */
    public function addCell(ListCell $cell, bool $prepend = false) {
        if($prepend) {
            $this->cells = array_merge([$cell], $this->cells);
        } else {
            $this->cells[] = $cell;
        }
    }

    public function output(): HTML {
        $this->html->id('row-' . $this->index);
        $this->html->text($this->processRender());

        return $this->html;
    }

    /**
     * Renders all cells in the row
     * 
     * @return string HTML code
     */
    private function processRender() {
        $content = '';

        foreach($this->cells as $cell) {
            $_cell = clone $cell;

            $content .= $_cell->output()->toString();
        }

        return $content;
    }
}

?>