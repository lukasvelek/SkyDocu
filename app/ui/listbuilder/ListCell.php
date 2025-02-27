<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;

/**
 * Class representing a cell in a list table
 * 
 * @author Lukas Velek
 */
class ListCell extends AListElement {
    public string|HTML $content;
    public HTML $html;
    private string $name;
    private bool $isHeader;

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();

        $this->content = '';
        $this->html = HTML::el('td');
        $this->name = '';
        $this->isHeader = false;
    }

    /**
     * Sets if the cell is a header
     * 
     * @param bool $header Is a header?
     */
    public function setHeader(bool $header = true) {
        $this->isHeader = $header;
    }

    /**
     * Sets the cell's name
     * 
     * @param string $name Cell name
     */
    public function setName(string $name) {
        $this->name = $name;
    }

    /**
     * Returns cell's name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the cell's content
     * 
     * @param string|HTML $content Cell content
     */
    public function setContent(string|HTML $content) {
        $this->content = $content;
    }

    /**
     * Sets the cell's class
     * 
     * @param string $class Cell class
     */
    public function setClass(string $class) {
        $this->attributes['class'] = $class;
    }

    /**
     * Sets the cell's colspan
     * 
     * @param int $span
     */
    public function setSpan(int $span) {
        $this->attributes['colspan'] = $span;
    }

    public function output(): HTML {
        if($this->isHeader) {
            $this->html->changeTag('th');
            $this->html->title($this->content);
        }

        $this->html->id('col-' . $this->name);
        $this->html->text($this->content);

        $this->appendAttributesToHtml($this->html);

        return $this->html;
    }
}

?>