<?php

namespace App\Components\ContainerExternalSystemsGrid;

use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Helpers\LinkHelper;
use App\Modules\APresenter;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ContainerExternalSystemsGrid extends GridBuilder implements IGridExtendingComponent {
    private string $containerId;

    public function __construct(GridBuilder $grid, string $containerId) {
        parent::__construct($grid->httpRequest);

        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);
        $this->setApplication($grid->app);

        $this->containerId = $containerId;

        $this->gridName = 'ExternalSystemsGrid';
    }

    public function createDataSource() {
        $qb = $this->app->externalSystemsRepository->composeQueryForExternalSystemsForContainer($this->containerId);

        $this->createDataSourceFromQueryBuilder($qb, 'systemId');
    }

    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();;
        $this->appendActions();

        parent::prerender();
    }

    private function appendSystemMetadata() {
        $this->addColumnText('title', 'Title');
        $this->addColumnBoolean('isEnabled', 'Enabled');
        $this->addColumnText('login', 'Login');
    }

    private function appendActions() {
        $info = $this->addAction('info');
        $info->setTitle('Information');
        $info->onCanRender[] = function() {
            return true;
        };
        $info->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createFullURLString('Admin:ExternalSystems', 'info', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Information');

            return $el;
        };

        $log = $this->addAction('log');
        $log->setTitle('Log');
        $log->onCanRender[] = function() {
            return true;
        };
        $log->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createFullURLString('Admin:ExternalSystems', 'log', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Log');

            return $el;
        };

        $rights = $this->addAction('rights');
        $rights->setTitle('Rights');
        $rights->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->isEnabled == false) {
                return false;
            }

            return true;
        };
        $rights->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createFullURLString('Admin:ExternalSystems', 'rightsList', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Rights');

            return $el;
        };
    }

    /**
     * Adds checkboxes to grid and forward the selected IDs to "actionBulkAction()"
     * 
     * @param APresenter $presenter Sender presenter
     */
    public function useCheckboxes(APresenter $presenter) {
        $this->addCheckboxes2($presenter, 'bulkAction');
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();

        return parent::actionGetSkeleton();
    }

    public function actionBulkAction(): JsonResponse {
        $links = $this->getBulkActionLinks($this->httpRequest->get('ids'));

        return new JsonResponse([
            'modal' => LinkHelper::createLinksFromArray($links)
        ]);
    }
}