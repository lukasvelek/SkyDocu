<?php

namespace App\Components\ContainersGrid;

use App\Constants\ContainerStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Helpers\GridHelper;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ContainersGrid extends GridBuilder implements IGridExtendingComponent {
    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();

        $this->appendActions();

        $this->setup();

        parent::prerender();
    }

    public function createDataSource() {
        $qb = $this->app->containerRepository->composeQueryForContainers();

        if(!$this->app->groupManager->isUserMemberOfContainerManagers($this->app->currentUser->getId())) {
            $qb->andWhere('userId = ?', [$this->app->currentUser->getId()]);
        }

        $this->createDataSourceFromQueryBuilder($qb, 'containerId');
    }

    private function appendSystemMetadata() {
        $this->addColumnText('title', 'Title');
        $this->addColumnConst('status', 'Status', ContainerStatus::class);
    }

    private function appendActions() {
        $settings = $this->addAction('settings');
        $settings->setTitle('Settings');
        $settings->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            $isContainerManager = $this->app->groupManager->isUserMemberOfContainerManagers($this->app->currentUser->getId());

            return ($isContainerManager && ($row->status != ContainerStatus::SCHEDULED_FOR_REMOVAL));
        };
        $settings->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdmin:ContainerSettings', 'home', ['containerId' => $primaryKey]))
                ->text('Settings');

            return $el;
        };
    }

    private function setup() {
        $this->setGridName(GridHelper::GRID_CONTAINERS);

        $this->addQuickSearch('title', 'Title');

        $this->addFilter('status', 'null', ContainerStatus::getAll());
    }

    public function actionBulkAction() {
        $links = $this->getBulkActionLinks($this->httpRequest->get('ids'));

        file_put_contents('__test3.txt', var_export($links, true));

        return new JsonResponse([
            'modal' => LinkHelper::createLinksFromArray($links)
        ]);
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();

        return parent::actionGetSkeleton();
    }
}