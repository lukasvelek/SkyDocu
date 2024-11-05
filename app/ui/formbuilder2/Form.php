<?php

namespace App\UI\FormBuilder2;

use App\UI\HTML\HTML;

class Form extends AElement {
    private array $rows;
    private string $name;
    private string $method;
    private array $action;

    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;

        $this->rows = [];
        $this->action = [];
        $this->method = 'POST';
    }

    public function addRow(Row $row) {
        $this->rows[] = $row;
    }

    public function setAction(array $action) {
        $this->action = $action;
    }

    public function setMethod(string $method = 'POST') {
        $this->method = $method;
    }

    public function render() {
        $form = HTML::el('form');
        $form->addAtribute('method', $this->method);
        $form->addAtribute('action', $this->processAction());
        $form->name($this->name);
        $form->id($this->name);
        $form->text($this->processRender());

        return $form;
    }

    private function processRender() {
        $code = '';

        foreach($this->rows as $row) {
            $code .= $row->render();
        }

        return $code;
    }

    private function processAction() {
        $parts = [];
        foreach($this->action as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        $url = '?' . implode('&', $parts);

        return $url;
    }
}

?>