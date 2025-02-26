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
    private bool $isSkeleton;

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

        $this->isSkeleton = false;

        $this->setName('checkbox');
    }

    public function output(): HTML {
        $this->setContent($this->getContent());
        
        return parent::output();
    }

    /**
     * Sets if this is a skeleton
     * 
     * @param bool $isSkeleton Is skeleton
     */
    public function setSkeleton(bool $isSkeleton = true) {
        $this->isSkeleton = $isSkeleton;
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

        if($this->isSkeleton) {
            $el = HTML::el('div')
                ->id('skeletonTextAnimation')
                ->text('x');
        }

        return $el;
    }
}

?>