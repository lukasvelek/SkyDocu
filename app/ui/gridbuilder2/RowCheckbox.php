<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

/**
 * Class representing row checkbox in a grid table
 * 
 * @author Lukas Velek
 */
class RowCheckbox extends Cell {
    private string $primaryKey;
    private string $handlerName;

    /**
     * Class constructor
     * 
     * @param string $primaryKey Row primary key
     * @param string $handlerName JS handler function name
     */
    public function __construct(string $primaryKey, string $handlerName) {
        parent::__construct();
        
        $this->primaryKey = $primaryKey;
        $this->handlerName = $handlerName;

        $this->setContent($this->getContent());
        $this->setName('checkbox');
    }

    /**
     * Create HTML code for checkbox
     * 
     * @return HTML HTML code
     */
    private function getContent() {
        $el = HTML::el('input')
                ->addAtribute('type', 'checkbox')
                ->addAtribute('name', 'col-checkbox-ids')
                ->value($this->primaryKey, true)
                ->addAtribute('onchange', $this->handlerName);

        return $el;
    }
}

?>