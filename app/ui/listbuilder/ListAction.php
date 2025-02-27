<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;
use Exception;

class ListAction implements IListHTMLOutput {
    public string $name;
    private string $title;

    /**
     * ArrayRow $row, ListRow $_row, Action &$action
     */
    public array $onCanRender;
    /**
     * int $index, ArrayRow $row, ListRow $_row, HTML $html
     */
    public array $onRender;

    private ArrayRow $row;
    private ListRow $_row;
    private HTML $html;
    private int $index;

    /**
     * Class constructor
     * 
     * @param string $name Action name
     */
    public function __construct(string $name) {
        $this->name = $name;

        $this->onCanRender = [];
        $this->onRender = [];

        $this->html = HTML::el('span');

        return $this;
    }

    /**
     * Injects mandatory parameters
     * 
     * @param ArrayRow $row ArrayRow instance
     * @param ListRow $_row ListRow instance
     * @param int $index Index
     */
    public function inject(ArrayRow $row, ListRow $_row, int $index) {
        $this->row = $row;
        $this->_row = $_row;
        $this->index = $index;
    }

    /**
     * Sets the title
     * 
     * @param string $title Action title
     */
    public function setTitle(string $title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Processes text displayed
     * 
     * @return string Text
     */
    private function processText(): string {
        $result = '-';

        if(!empty($this->onRender)) {
            foreach($this->onRender as $render) {
                try {
                    $result = $render($this->index, $this->row, $this->_row, $this->html);
                } catch(Exception $e) {
                    $result = '#ERROR';
                    $this->title = $e->getMessage();
                }
            }
        } else {
            $result = '-';
        }

        if($result === null) {
            $result = '-';
        }

        return $result;
    }

    public function output(): HTML {
        $this->html->id('col-actions-' . $this->name);

        $this->html->text($this->processText());

        if(isset($this->title)) {
            $this->html->title($this->title);
        }

        return $this->html;
    }
}

?>