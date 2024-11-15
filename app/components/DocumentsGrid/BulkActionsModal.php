<?php

namespace App\Components\DocumentsGrid;

use App\UI\AComponent;
use App\UI\ModalBuilder\ModalBuilder;

/**
 * Modal containing bulk actions
 * 
 * @author Lukas Velek
 */
class BulkActionsModal extends ModalBuilder {
    private string $gridName;
    private AComponent $grid;

    private array $bulkActions;

    /**
     * Class constructor
     * 
     * @param AComponent $grid Calling grid
     */
    public function __construct(AComponent $grid) {
        parent::__construct($grid->httpRequest);

        $this->gridName = $grid->componentName;
        $this->grid = $grid;
        $this->bulkActions = [];

        $this->componentName = $this->gridName . '_bulk_actions';
        $this->templateFile = __DIR__ . '/bulk-actions.html';
        $this->setTitle('Bulk actions');
        $this->setId('bulk-actions');
    }

    /**
     * Sets bulk action links
     * 
     * @param array $bulkActions Bulk action links
     */
    public function setBulkActions(array $bulkActions) {
        $this->bulkActions = $bulkActions;
    }

    public function startup() {
        parent::startup();

        $code = '<table><tr style="height: 50px">';

        foreach($this->bulkActions as $bulkAction) {
            $code .= '<td>' . $bulkAction . '</td>';
        }

        $code .= '</tr></table>';

        $this->content = $code;
    }
}

?>