<?php

namespace App\UI\FormBuilder2;

use App\Core\AjaxRequestBuilder;
use App\UI\HTML\HTML;

/**
 * Form instance
 */
class Form extends AElement {
    private array $rows;
    private string $name;
    private string $method;
    private array $action;
    public array $scripts;

    /**
     * Class constructor
     * 
     * @param string $name Form name
     */
    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;

        $this->rows = [];
        $this->action = [];
        $this->method = 'POST';
        $this->scripts = [];
    }

    /**
     * Adds JS script
     * 
     * @param AjaxRequestBuilder|string $script JS script
     */
    public function addScript(AjaxRequestBuilder|string $script) {
        if($script instanceof AjaxRequestBuilder) {
            $script = $script->build();
        }

        $this->scripts[] = $script;
    }

    /**
     * Adds layout row
     * 
     * @param Row $row Row instance
     */
    public function addRow(Row $row) {
        $this->rows[] = $row;
    }

    /**
     * Sets form action
     * 
     * @param array $action Action
     */
    public function setAction(array $action) {
        $this->action = $action;
    }

    /**
     * Sets form method
     * 
     * @param string $method Method
     */
    public function setMethod(string $method = 'POST') {
        $this->method = $method;
    }

    /**
     * Renders the form to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $form = HTML::el('form');
        $form->addAtribute('method', $this->method);
        $form->addAtribute('action', $this->processAction());
        $form->name($this->name);
        $form->id($this->name);
        $form->text($this->renderRows());

        return $form;
    }

    /**
     * Processes layout row render
     * 
     * @return string HTML code
     */
    private function renderRows() {
        $code = '';

        foreach($this->rows as $row) {
            $code .= $row->render()/* . '<br>'*/;
        }

        return $code;
    }

    /**
     * Creates a string URL from action
     * 
     * @return string URL
     */
    private function processAction() {
        if(!array_key_exists('isFormSubmit', $this->action)) {
            $this->action['isFormSubmit'] = '1';
        }

        $parts = [];
        foreach($this->action as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        $url = '?' . implode('&', $parts);

        return $url;
    }
}

?>