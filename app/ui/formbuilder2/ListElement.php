<?php

namespace App\UI\FormBuilder2;

use App\UI\ListBuilder\ListBuilder;

/**
 * ListElement allows displaying a list (instance of ListBuilder) in a form
 * 
 * @author Lukas Velek
 */
class ListElement extends AElement {
    private string $name;
    private ListBuilder $list;

    public function __construct(string $name, ListBuilder $list) {
        parent::__construct();

        $this->name = $name;
        $this->list = $list;
    }

    public function render() {
        $code = '<div id="grid"><div id="' . $this->name . '">';

        $code .= $this->internalListRender();

        $code .= '</div></div>';

        return $code;
    }

    /**
     * Renders the ListBuilder component itself
     */
    private function internalListRender(): string {
        $this->list->startup();
        $this->list->prerender();
        return $this->list->render();
    }
}