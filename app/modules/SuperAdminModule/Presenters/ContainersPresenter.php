<?php

namespace App\Modules\SuperAdminModule;

use App\Core\Http\HttpRequest;
use App\Helpers\GridHelper;
use App\UI\LinkBuilder;

class ContainersPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainersPresenter', 'Containers');
    }

    public function handleList() {}

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New container', $this->createURL('newContainerForm'), 'link')
        ];
    }

    protected function createComponentContainersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->containerRepository->composeQueryForContainers(), 'containerId');
        $grid->setGridName(GridHelper::GRID_CONTAINERS);

        $grid->addColumnText('title', 'Title');
        
        return $grid;
    }
}

?>