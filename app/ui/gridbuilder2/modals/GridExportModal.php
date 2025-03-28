<?php

namespace App\UI\GridBuilder2;

use App\Exceptions\AException;
use App\UI\AComponent;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\ModalBuilder\ModalBuilder;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * Modal that allows controlling exporting from grids
 * 
 * @author Lukas Velek
 */
class GridExportModal extends ModalBuilder {
    private QueryBuilder $dataSource;
    private string $gridComponentName;
    private array $gridQueryDependencies;

    /**
     * Class constructor
     * 
     * @param AComponent $grid Grid
     */
    public function __construct(AComponent $grid) {
        parent::__construct($grid->httpRequest);

        $this->setId('grid-export');
        $this->setTitle('Grid export');

        $this->gridComponentName = $grid->componentName;
        $this->gridQueryDependencies = [];
    }

    /**
     * Sets the grid query dependencies
     * 
     * @param array $gridQueryDependencies Grid query dependencies
     */
    public function setGridQueryDependencies(array $gridQueryDependencies) {
        $this->gridQueryDependencies = $gridQueryDependencies;
    }

    /**
     * Sets the data source
     * 
     * @param QueryBuilder $qb QueryBuilder instance
     */
    public function setDataSource(QueryBuilder $qb) {
        $this->dataSource = $qb;
    }

    /**
     * Renders the modal content
     * 
     * @return string HTML code
     */
    public function render() {
        $this->setContentFromFormBuilder($this->createForm());
        return parent::render();
    }

    /**
     * Sets the grid component name
     * 
     * @param string $gridComponentName
     */
    public function setGridComponentName(string $gridComponentName) {
        $this->gridComponentName = $gridComponentName;
    }

    /**
     * Creates the modal form
     * 
     * @return FormBuilder2 FormBuilder2 instance
     */
    private function createForm() {
        $args = [];
        foreach($this->gridQueryDependencies as $gqd) {
            $args[] = '\'' . $gqd . '\'';
        }

        $form = new FormBuilder2($this->httpRequest);

        if($this->isOverLimit()) {
            $form->addButton('Export to the limit')
                ->setOnClick($this->gridComponentName . '_exportLimited(' . implode(', ', $args) . ')');

            $form->addButton('Export all')
                ->setOnClick($this->gridComponentName . '_exportUnlimited(' . implode(', ', $args) . ')');
        } else {
            $form->addButton('Export')
                ->setOnClick($this->gridComponentName . '_exportLimited(' . implode(', ', $args) . ')');
        }

        return $form;
    }

    /**
     * Checks if the exporting can be done asynchronously
     * 
     * @return bool True if asynchronous exporting is allowed or false if not
     */
    private function isOverLimit() {
        $ds = clone $this->dataSource;

        try {
            $count = $ds->execute()->fetchAll()->num_rows;
        } catch(AException|Exception $e) {
            $count = 0;
        }
        
        return $count >= MAX_GRID_EXPORT_SIZE;
    }
}

?>