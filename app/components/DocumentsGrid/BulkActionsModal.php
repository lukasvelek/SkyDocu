<?php

namespace App\Components\DocumentsGrid;

use App\UI\AComponent;
use App\UI\ModalBuilder\ModalBuilder;

class BulkActionsModal extends ModalBuilder {
    private string $gridName;
    private AComponent $grid;

    private array $bulkActions;

    public function __construct(AComponent $grid) {
        parent::__construct($grid->httpRequest);

        $this->gridName = $grid->componentName;
        $this->grid = $grid;
        $this->bulkActions = [];

        $this->componentName = $this->gridName . '_bulk_actions';
        $this->templateFile = __DIR__ . '/bulk-actions.html';
        $this->setId('bulk-actions');
    }

    public function setBulkActions(array $bulkActions) {
        $this->bulkActions = $bulkActions;
    }

    public function startup() {
        parent::startup();

        $code = '<table><tr>';

        foreach($this->bulkActions as $bulkAction) {
            $code .= '<td>' . $bulkAction . '</td>';
        }

        $code .= '</tr></table>';

        $this->content = $code;
    }
}

?>