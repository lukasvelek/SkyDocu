<?php

namespace App\Components\DocumentsGrid;

use App\Constants\AppDesignThemes;
use App\Helpers\AppThemeHelper;
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

        $this->app = $grid->app;
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

        $count = 0;
        foreach($this->bulkActions as $bulkAction) {
            if($count == 4) {
                $code .= '</tr><tr style="height: 50px">';
                $count = 0;
            }

            $code .= '<td>' . $bulkAction . '</td>';

            $count++;
        }

        $code .= '</tr></table>';

        $this->content = $code;

        $this->processBackgroundColor();
        $this->processTitle();
    }

    /**
     * Implicitly sets modal's background color based on user's selected application theme
     */
    private function processBackgroundColor() {
        if(AppThemeHelper::getAppThemeForUser($this->app) == AppDesignThemes::DARK) {
            $this->template->background_color = 'rgba(70, 70, 70, 1)';
        } else {
            $this->template->background_color = 'rgba(225, 225, 225, 1)';
        }
    }

    /**
     * Implicitly sets modal's title based on user's selected application theme
     */
    private function processTitle() {
        if(AppThemeHelper::getAppThemeForUser($this->app) == AppDesignThemes::LIGHT) {
            $this->title = '<span style="color: black">' . $this->title . '</span>';
        } else {
            $this->title = '<span>' . $this->title . '</span>';
        }
    }
}

?>