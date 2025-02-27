<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;

/**
 * Class that represents the list table
 * 
 * @author Lukas Velek
 */
class ListTable extends AListElement {
    /**
     * @var array<int, ListRow> $rows
     */
    private array $rows;

    /**
     * Class constructor
     * 
     * @param array $rows List rows
     */
    public function __construct(array $rows) {
        parent::__construct();

        $this->rows = $rows;
    }

    public function output(): HTML {
        $table = HTML::el('table');
        $table->text($this->processRender());
        $table->addAtribute('border', '1');

        return $table;
    }

    /**
     * Renders the list content to HTML code
     * 
     * @return string HTML code
     */
    private function processRender() {
        $content = '';

        $first = true;
        foreach($this->rows as $row) {
            $_row = clone $row;

            if($first) {
                $content .= '<thead>' . $_row->output()->toString() . '</thead>';
                $first = false;
                $content .= '<tbody>';
                continue;
            }

            $content .= $_row->output()->toString();
        }
        $content .= '</tbody>';

        return $content;
    }
}

?>