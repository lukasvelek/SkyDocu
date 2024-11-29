<?php

namespace App\Modules\AdminModule;

use App\Core\Http\HttpRequest;
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

        return $grid;
    }
}

?>