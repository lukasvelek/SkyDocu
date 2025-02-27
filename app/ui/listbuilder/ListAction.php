<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;
use Exception;

class ListAction implements IListHTMLOutput {
    public string $name;
    private string $title;

    /**
     * ArrayRow $row, ListRow $_row, ListAction &$action
     */
    public array $onCanRender;
    /**
     * mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html
     */
    public array $onRender;

    private ArrayRow $row;
    private ListRow $_row;
    private HTML $html;
    private mixed $primaryKey;

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
     * @param mixed $primaryKey Primary key
     */
    public function inject(ArrayRow $row, ListRow $_row, mixed $primaryKey) {
        $this->row = $row;
        $this->_row = $_row;
        $this->primaryKey = $primaryKey;
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
    private function processText(): string|HTML {
        $result = '-';

        if(!empty($this->onRender)) {
            foreach($this->onRender as $render) {
                try {
                    $result = $render($this->primaryKey, $this->row, $this->_row, $this->html);
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