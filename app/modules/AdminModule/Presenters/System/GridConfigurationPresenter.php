<?php

namespace App\Modules\AdminModule;

use App\Components\GridConfigurationForm\GridConfigurationForm;
use App\Constants\Container\GridNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class GridConfigurationPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('GridConfigurationPresenter', 'Grid configuration');

        $this->setSystem();
    }

    public function renderList() {
        $this->template->links = LinkBuilder::createSimpleLink('New configuration', $this->createURL('newConfigurationForm'), 'link');
    }

    protected function createComponentGridConfigurationGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->setGridName('gridConfigurationGrid');
        $grid->createDataSourceFromQueryBuilder($this->gridManager->composeQueryForGridConfigurations(), 'configurationId');

        $grid->addColumnConst('gridName', 'Grid', GridNames::class);
        $col = $grid->addColumnText('columnConfiguration', 'Visible columns');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $parts = explode(';', $value);

            $parts2 = [];
            foreach($parts as $part) {
                $parts2[] = ucfirst($part);
            }

            return implode(', ', $parts2);
        };

        return $grid;
    }

    public function handleNewConfigurationForm(?FormResponse $fr = null) {
        if($fr !== null) {
            try {
                $columns = [];

                foreach($fr->getAllValues() as $key => $value) {
                    if(in_array($key, ['btn_submit', 'gridName'])) {
                        continue;
                    }

                    if($value == 'on') {
                        $columns[] = $key;
                    }
                }
                
                $this->gridRepository->beginTransaction(__METHOD__);

                $this->gridManager->createGridConfiguration($fr->gridName, $columns);

                $this->gridRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Grid configuration created.', 'success');
            } catch(AException $e) {
                $this->gridRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create grid configuration.', 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewConfigurationForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewGridConfigurationForm(HttpRequest $request) {
        $form = new GridConfigurationForm($request, $this->gridManager);

        return $form;
    }
}

?>