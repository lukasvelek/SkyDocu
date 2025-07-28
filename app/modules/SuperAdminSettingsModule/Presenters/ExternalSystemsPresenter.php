<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ExternalSystemsPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('ExternalSystemsPresenter', 'External systems');
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentExternalSystemsGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->externalSystemsRepository->composeQueryForExternalSystems();

        $grid->createDataSourceFromQueryBuilder($qb, 'systemId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        
        $col = $grid->addColumnText('containerId', 'Container');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if($row->containerId === null) {
                return '-';
            } else {
                try {
                    $container = $this->app->containerManager->getContainerById($row->containerId);

                    $el = HTML::el('span');

                    $el->title($row->containerId);
                    $el->text($container->getTitle());

                    return $el;
                } catch(AException $e) {
                    return '-';
                }
            }
        };

        $grid->addColumnBoolean('isEnabled', 'Enabled');

        return $grid;
    }
}